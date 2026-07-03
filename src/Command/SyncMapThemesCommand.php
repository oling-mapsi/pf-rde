<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Taxonomy\MapThemeCatalog;
use App\Domain\Taxonomy\Entity\TaxonomyTerm;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:themes:sync-map-theme',
    description: 'Synchronise les thèmes existants (sources + cartes statiques) vers la taxonomie map_theme.',
)]
final class SyncMapThemesCommand extends Command
{
    /** @var list<string> */
    private const DEFAULT_COLORS = ['#FC5000', '#38B4E7', '#AAAE02', '#FBD002'];

    /** @var list<string> */
    private const DEFAULT_ICONS = ['route', 'layers', 'map', 'shield'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule la synchronisation sans écrire en base.')
            ->addOption('activate-all', null, InputOption::VALUE_NONE, 'Active les thèmes existants map_theme inactifs pendant la synchro.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $activateAll = (bool) $input->getOption('activate-all');

        $rawThemeLabels = $this->fetchDistinctThemeLabels();
        if ($rawThemeLabels === []) {
            $io->warning('Aucun thème trouvé dans data_source.theme et static_map.theme.');

            return Command::SUCCESS;
        }

        $sourceLabels = $this->normalizeSourceLabels($rawThemeLabels);
        $repository = $this->entityManager->getRepository(TaxonomyTerm::class);
        /** @var list<TaxonomyTerm> $existingTerms */
        $existingTerms = $repository->findBy(['taxonomy' => TaxonomyTerm::MAP_THEME_TAXONOMY]);

        $slugger = new AsciiSlugger();
        $existingByLabel = [];
        $existingBySlug = [];
        $positionMax = 0;

        foreach ($existingTerms as $term) {
            $existingByLabel[$this->normalizeKey($term->getLabel())] = $term;
            $existingBySlug[$term->getSlug()] = $term;
            $positionMax = max($positionMax, $term->getPosition());
        }

        $created = 0;
        $updated = 0;
        $activated = 0;
        $currentColorIndex = 0;
        $currentIconIndex = 0;
        $rows = [];

        foreach ($sourceLabels as $label) {
            $normalized = $this->normalizeKey($label);
            $slugBase = $this->slugify($slugger, $label);
            $officialDefinition = MapThemeCatalog::definitionForLabel($label);

            $term = $existingByLabel[$normalized] ?? null;
            if ($term === null) {
                $term = $existingBySlug[$slugBase] ?? null;
            }
            if ($officialDefinition !== null && isset($existingBySlug[$officialDefinition['slug']])) {
                $term = $existingBySlug[$officialDefinition['slug']];
            }

            $action = 'kept';

            if ($term === null) {
                $term = new TaxonomyTerm();
                $term
                    ->setTaxonomy(TaxonomyTerm::MAP_THEME_TAXONOMY)
                    ->setLabel($label)
                    ->setSlug($officialDefinition['slug'] ?? $this->nextUniqueSlug($slugBase, $existingBySlug))
                    ->setDescription($officialDefinition['description'] ?? 'Thème synchronisé automatiquement depuis les ressources cartographiques.')
                    ->setActive(true)
                    ->setFeaturedOnHomepage($officialDefinition !== null)
                    ->setPosition($officialDefinition['position'] ?? ($positionMax + 10))
                    ->setColorHex($officialDefinition['color'] ?? self::DEFAULT_COLORS[$currentColorIndex % count(self::DEFAULT_COLORS)])
                    ->setIconKey($officialDefinition['icon'] ?? self::DEFAULT_ICONS[$currentIconIndex % count(self::DEFAULT_ICONS)]);

                ++$created;
                if ($officialDefinition === null) {
                    $positionMax += 10;
                    ++$currentColorIndex;
                    ++$currentIconIndex;
                }
                $action = 'created';

                if (!$dryRun) {
                    $this->entityManager->persist($term);
                }

                $existingBySlug[$term->getSlug()] = $term;
                $existingByLabel[$normalized] = $term;
            } else {
                $changed = false;
                if ($officialDefinition !== null) {
                    $term
                        ->setLabel($officialDefinition['label'])
                        ->setDescription($officialDefinition['description'])
                        ->setColorHex($officialDefinition['color'])
                        ->setIconKey($officialDefinition['icon'])
                        ->setPosition($officialDefinition['position'])
                        ->setFeaturedOnHomepage(true);
                    if (trim($term->getSlug()) === '') {
                        $term->setSlug($officialDefinition['slug']);
                    }
                    $changed = true;
                }

                if ($activateAll && !$term->isActive()) {
                    $term->setActive(true);
                    ++$activated;
                    $changed = true;
                }

                if (trim($term->getLabel()) === '') {
                    $term->setLabel($label);
                    $changed = true;
                }

                if (trim($term->getSlug()) === '') {
                    $term->setSlug($this->nextUniqueSlug($slugBase, $existingBySlug));
                    $existingBySlug[$term->getSlug()] = $term;
                    $changed = true;
                }

                if ($changed) {
                    ++$updated;
                    $action = 'updated';
                }
            }

            $rows[] = [
                $label,
                $term->getSlug(),
                $action,
                $term->isActive() ? 'oui' : 'non',
                $term->isFeaturedOnHomepage() ? 'oui' : 'non',
            ];
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->title('Synchronisation des thèmes cartothèque vers map_theme');
        $io->table(['Libellé', 'Slug', 'Action', 'Actif', 'En avant accueil'], $rows);
        $io->success(sprintf(
            'Terminé%s : %d créé(s), %d mis à jour, %d activé(s), %d inchangé(s).',
            $dryRun ? ' (dry-run)' : '',
            $created,
            $updated,
            $activated,
            max(0, count($rows) - $created - $updated),
        ));

        if ($dryRun) {
            $io->note('Relance sans --dry-run pour appliquer la synchronisation.');
        }

        return Command::SUCCESS;
    }

    /** @return list<string> */
    private function fetchDistinctThemeLabels(): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT trimmed.theme
            FROM (
                SELECT TRIM(theme) AS theme FROM data_source WHERE theme IS NOT NULL
                UNION
                SELECT TRIM(theme) AS theme FROM static_map WHERE theme IS NOT NULL
            ) AS trimmed
            WHERE trimmed.theme <> ''
            ORDER BY trimmed.theme ASC
        SQL;

        /** @var list<string> $rows */
        $rows = $this->connection->fetchFirstColumn($sql);

        return array_values(array_filter(array_map(
            static fn (mixed $label): string => trim((string) $label),
            $rows
        )));
    }

    /**
     * @param list<string> $labels
     *
     * @return list<string>
     */
    private function normalizeSourceLabels(array $labels): array
    {
        $result = [];
        $seen = [];

        foreach ($labels as $label) {
            $label = MapThemeCatalog::normalizeLabel($label);
            $normalized = $this->normalizeKey($label);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $result[] = preg_replace('/\s+/', ' ', trim($label)) ?? trim($label);
        }

        return $result;
    }

    private function normalizeKey(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }

    /**
     * @param array<string, TaxonomyTerm> $existingBySlug
     */
    private function nextUniqueSlug(string $base, array $existingBySlug): string
    {
        if (!isset($existingBySlug[$base])) {
            return $base;
        }

        $i = 2;
        while (isset($existingBySlug[$base.'-'.$i])) {
            ++$i;
        }

        return $base.'-'.$i;
    }

    private function slugify(AsciiSlugger $slugger, string $label): string
    {
        $slug = trim(strtolower($slugger->slug($label)->toString()));

        return $slug !== '' ? $slug : 'theme';
    }
}
