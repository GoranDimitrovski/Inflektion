import { ApplicationConfig, inject, provideAppInitializer, provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideRouter, Router } from '@angular/router';
import { client } from './api/client.gen';
import { routes } from './app.routes';
import { SessionService } from './core/session';

client.setConfig({ credentials: 'include' });

// Sanctum's stateful/session auth requires the XSRF-TOKEN cookie (primed by
// SessionService.bootstrap() via /sanctum/csrf-cookie) echoed back as
// X-XSRF-TOKEN on every write — the generated client doesn't do this on its
// own, unlike SessionService's own hand-rolled fetch calls for login/logout.
// Without this, any POST/PATCH/DELETE through the generated client (creating
// a program, inviting a member, etc.) gets a 419 CSRF token mismatch.
client.interceptors.request.use((request) => {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

  if (match) {
    request.headers.set('X-XSRF-TOKEN', decodeURIComponent(match[1]));
  }

  // The generated SDK hardcodes "application/json"; the /v1 JSON:API routes
  // reject anything but the vendor media type. Only /v1 — the auth and 2FA
  // routes outside it are plain JSON and reject the vendor type instead.
  if (request.url.includes('/v1/')) {
    request.headers.set('Accept', 'application/vnd.api+json');

    if (request.headers.has('Content-Type')) {
      request.headers.set('Content-Type', 'application/vnd.api+json');
    }
  }

  return request;
});

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(routes),
    provideAppInitializer(() => {
      const router = inject(Router);
      const session = inject(SessionService);

      client.interceptors.response.use((response) => {
        if (response.status === 401) {
          void router.navigate(['/login']);
        }

        return response;
      });

      return session.bootstrap();
    }),
  ],
};
