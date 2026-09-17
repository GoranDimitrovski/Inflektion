import { Routes } from '@angular/router';
import { requireAuth, requirePermission, setActiveAccountFromRoute } from './core/guards';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login-page').then((m) => m.LoginPage),
  },
  {
    path: 'forgot-password',
    loadComponent: () => import('./features/auth/forgot-password-page').then((m) => m.ForgotPasswordPage),
  },
  {
    path: 'reset-password',
    loadComponent: () => import('./features/auth/reset-password-page').then((m) => m.ResetPasswordPage),
  },
  {
    path: 'invitations/:token',
    loadComponent: () => import('./features/invitations/accept-invitation-page').then((m) => m.AcceptInvitationPage),
  },
  {
    path: 'accounts/:accountId',
    canActivate: [requireAuth, setActiveAccountFromRoute],
    children: [
      {
        path: 'programs',
        canActivate: [requirePermission('programs.read')],
        loadComponent: () => import('./features/programs/programs-page').then((m) => m.ProgramsPage),
      },
      {
        // Not gated by requirePermission: a plain Member/Viewer can still
        // reach this page to leave the account; MembersPage hides the rest.
        path: 'members',
        loadComponent: () => import('./features/members/members-page').then((m) => m.MembersPage),
      },
    ],
  },
  { path: '', redirectTo: 'login', pathMatch: 'full' },
];
