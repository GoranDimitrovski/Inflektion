import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: 'programs',
    loadComponent: () => import('./features/programs/programs-page').then((m) => m.ProgramsPage),
  },
  { path: '', redirectTo: 'programs', pathMatch: 'full' },
];
