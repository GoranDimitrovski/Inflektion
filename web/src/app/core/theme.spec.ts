import { TestBed } from '@angular/core/testing';
import { ThemeService } from './theme';

function stubMatchMedia(prefersDark: boolean): void {
  vi.stubGlobal(
    'matchMedia',
    vi.fn(() => ({ matches: prefersDark })),
  );
}

describe('ThemeService', () => {
  beforeEach(() => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-theme');
    TestBed.resetTestingModule();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    localStorage.clear();
  });

  it('follows the system preference when nothing is stored', () => {
    stubMatchMedia(true);

    expect(TestBed.inject(ThemeService).theme()).toBe('dark');
  });

  it('prefers an explicitly stored choice over the system preference', () => {
    localStorage.setItem('theme', 'light');
    stubMatchMedia(true);

    expect(TestBed.inject(ThemeService).theme()).toBe('light');
  });

  it('falls back to light when matchMedia is unavailable', () => {
    vi.stubGlobal('matchMedia', undefined);

    expect(TestBed.inject(ThemeService).theme()).toBe('light');
  });

  it('applies the theme to the document and persists it', () => {
    stubMatchMedia(false);
    const service = TestBed.inject(ThemeService);

    TestBed.tick();
    expect(document.documentElement.getAttribute('data-theme')).toBe('light');

    service.toggle();
    TestBed.tick();

    expect(service.theme()).toBe('dark');
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark');
    expect(localStorage.getItem('theme')).toBe('dark');
  });

  it('toggles back and forth', () => {
    stubMatchMedia(false);
    const service = TestBed.inject(ThemeService);

    service.toggle();
    service.toggle();

    expect(service.theme()).toBe('light');
  });
});
