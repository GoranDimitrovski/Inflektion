import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { SessionService } from './session';

export const requireAuth: CanActivateFn = () => {
  const session = inject(SessionService);
  const router = inject(Router);

  return session.isAuthenticated() ? true : router.createUrlTree(['/login']);
};

/** Sets the active account from the route's :accountId before permission guards run. */
export const setActiveAccountFromRoute: CanActivateFn = (route) => {
  const session = inject(SessionService);
  const accountId = Number(route.paramMap.get('accountId'));

  if (!Number.isNaN(accountId)) {
    session.setActiveAccountId(accountId);
  }

  return true;
};

export const requirePermission =
  (permission: string): CanActivateFn =>
  () => {
    const session = inject(SessionService);
    const router = inject(Router);

    return session.can(permission) ? true : router.createUrlTree(['/access-denied']);
  };
