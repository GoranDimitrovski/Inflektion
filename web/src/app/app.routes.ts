import { Routes } from '@angular/router';
import { requireAuth, requirePermission, setActiveAccountFromRoute } from './core/guards';

export const routes: Routes = [
  {
    path: 'login',
    title: 'Log in · Inflection',
    loadComponent: () => import('./features/auth/login-page').then((m) => m.LoginPage),
  },
  {
    path: 'register',
    title: 'Create an account · Inflection',
    loadComponent: () => import('./features/auth/register-page').then((m) => m.RegisterPage),
  },
  {
    path: 'forgot-password',
    title: 'Reset your password · Inflection',
    loadComponent: () => import('./features/auth/forgot-password-page').then((m) => m.ForgotPasswordPage),
  },
  {
    path: 'reset-password',
    title: 'Choose a new password · Inflection',
    loadComponent: () => import('./features/auth/reset-password-page').then((m) => m.ResetPasswordPage),
  },
  {
    path: 'invitations/:token',
    title: 'Accept invitation · Inflection',
    loadComponent: () => import('./features/invitations/accept-invitation-page').then((m) => m.AcceptInvitationPage),
  },
  {
    path: 'access-denied',
    title: 'Access denied · Inflection',
    loadComponent: () => import('./core/access-denied').then((m) => m.AccessDenied),
  },
  {
    path: 'accounts/:accountId',
    canActivate: [requireAuth, setActiveAccountFromRoute],
    loadComponent: () => import('./core/account-shell').then((m) => m.AccountShell),
    children: [
      {
        path: 'programs',
        title: 'Programs · Inflection',
        canActivate: [requirePermission('programs.read')],
        loadComponent: () => import('./features/programs/programs-page').then((m) => m.ProgramsPage),
      },
      {
        path: 'links',
        title: 'Links · Inflection',
        canActivate: [requirePermission('programs.read')],
        loadComponent: () => import('./features/links/links-page').then((m) => m.LinksPage),
      },
      {
        // Not gated by requirePermission: a plain Member/Viewer can still
        // reach this page to leave the account; MembersPage hides the rest.
        path: 'members',
        title: 'Members · Inflection',
        loadComponent: () => import('./features/members/members-page').then((m) => m.MembersPage),
      },
      {
        // Not gated: 2FA is a personal setting and ApiTokenPolicy allows any
        // member to view/create/revoke their own tokens.
        path: 'settings',
        title: 'Settings · Inflection',
        loadComponent: () => import('./features/settings/settings-page').then((m) => m.SettingsPage),
      },
      { path: '', redirectTo: 'programs', pathMatch: 'full' },
    ],
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  // Without this, any unmatched URL throws NG04002 instead of landing somewhere.
  { path: '**', redirectTo: '' },
];
