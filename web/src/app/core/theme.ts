import { Injectable, effect, signal } from '@angular/core';

export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'theme';

function initialTheme(): Theme {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);

    if (stored === 'light' || stored === 'dark') {
      return stored;
    }
  } catch {
    // localStorage can be unavailable (private browsing, etc.) — fall back to system preference.
  }

  if (typeof matchMedia !== 'function') {
    return 'light';
  }

  return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** App-wide dark/light mode, persisted locally and applied via a `data-theme` attribute on `<html>` (see styles.scss). */
@Injectable({ providedIn: 'root' })
export class ThemeService {
  readonly theme = signal<Theme>(initialTheme());

  constructor() {
    effect(() => {
      const theme = this.theme();

      document.documentElement.setAttribute('data-theme', theme);

      try {
        localStorage.setItem(STORAGE_KEY, theme);
      } catch {
        // Theme still applies for this page load — it just won't persist across visits.
      }
    });
  }

  toggle(): void {
    this.theme.update((current) => (current === 'dark' ? 'light' : 'dark'));
  }
}
