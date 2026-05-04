import { startStimulusApp } from '@symfony/stimulus-bundle';
import CatalogController from './controllers/catalog_controller.js';
import AsyncFormController from './controllers/async_form_controller.js';
import DashboardRefreshController from './controllers/dashboard_refresh_controller.js';
import CookieConsentController from './controllers/cookie_consent_controller.js';
import SortTableController from './controllers/sort_table_controller.js';
import InteractiveMapController from './controllers/interactive_map_controller.js';

const app = startStimulusApp();

app.register('catalog', CatalogController);
app.register('async-form', AsyncFormController);
app.register('dashboard-refresh', DashboardRefreshController);
app.register('cookie-consent', CookieConsentController);
app.register('sort-table', SortTableController);
app.register('interactive-map', InteractiveMapController);
