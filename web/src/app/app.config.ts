import { ApplicationConfig, provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideRouter } from '@angular/router';
import { client } from './api/client.gen';
import { routes } from './app.routes';

// The API is JSON:API (laravel-json-api), which rejects any request whose
// Content-Type/Accept isn't exactly "application/vnd.api+json" — the
// generated client defaults to "application/json".
client.setConfig({
  headers: {
    Accept: 'application/vnd.api+json',
    'Content-Type': 'application/vnd.api+json',
  },
});

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes)
  ]
};
