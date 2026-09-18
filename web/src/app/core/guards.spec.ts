import { TestBed } from '@angular/core/testing';
import { ActivatedRouteSnapshot, convertToParamMap, provideRouter, Router, RouterStateSnapshot, UrlTree } from '@angular/router';
import { routes } from '../app.routes';
import { requireAuth, requirePermission, setActiveAccountFromRoute } from './guards';
import { SessionService } from './session';

const emptyRoute = {} as ActivatedRouteSnapshot;
const emptyState = {} as RouterStateSnapshot;

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

async function authenticate(session: SessionService, permissions: string[]): Promise<void> {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValueOnce(
      jsonResponse({
        data: {
          user: { id: 1, name: 'Ada', email: 'ada@example.test' },
          memberships: [{ account: { id: 5, name: 'Acme', slug: 'acme' }, role: 'owner', permissions }],
        },
      }),
    ),
  );

  await session.load();
  vi.unstubAllGlobals();
}

describe('guards', () => {
  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideRouter([])],
    });
  });

  describe('requireAuth', () => {
    it('allows navigation when authenticated', async () => {
      const session = TestBed.inject(SessionService);
      await authenticate(session, ['programs.read']);

      const result = TestBed.runInInjectionContext(() => requireAuth(emptyRoute, emptyState));

      expect(result).toBe(true);
    });

    it('redirects to /login when not authenticated', () => {
      const router = TestBed.inject(Router);

      const result = TestBed.runInInjectionContext(() => requireAuth(emptyRoute, emptyState));

      expect(router.serializeUrl(result as UrlTree)).toBe('/login');
    });
  });

  describe('setActiveAccountFromRoute', () => {
    it('sets the active account id from the :accountId route param', async () => {
      const session = TestBed.inject(SessionService);
      await authenticate(session, ['programs.read', 'programs.write']);

      const route = { paramMap: convertToParamMap({ accountId: '5' }) } as unknown as ActivatedRouteSnapshot;
      const result = TestBed.runInInjectionContext(() => setActiveAccountFromRoute(route, emptyState));

      expect(result).toBe(true);
      expect(session.activeMembership()?.account.id).toBe(5);
    });

    it('leaves the active account unset for a non-numeric param, rather than crashing', () => {
      const route = { paramMap: convertToParamMap({ accountId: 'not-a-number' }) } as unknown as ActivatedRouteSnapshot;

      const result = TestBed.runInInjectionContext(() => setActiveAccountFromRoute(route, emptyState));

      expect(result).toBe(true);
    });
  });

  describe('requirePermission', () => {
    it('allows navigation when the active membership has the permission', async () => {
      const session = TestBed.inject(SessionService);
      await authenticate(session, ['programs.read', 'programs.write']);
      session.setActiveAccountId(5);

      const result = TestBed.runInInjectionContext(() => requirePermission('programs.write')(emptyRoute, emptyState));

      expect(result).toBe(true);
    });

    it('redirects to /access-denied when the active membership lacks the permission', async () => {
      const session = TestBed.inject(SessionService);
      await authenticate(session, ['programs.read']);
      session.setActiveAccountId(5);
      const router = TestBed.inject(Router);

      const result = TestBed.runInInjectionContext(() => requirePermission('programs.write')(emptyRoute, emptyState));

      expect(router.serializeUrl(result as UrlTree)).toBe('/access-denied');
    });

    it('redirects to /access-denied when no account is active at all', () => {
      const router = TestBed.inject(Router);

      const result = TestBed.runInInjectionContext(() => requirePermission('programs.read')(emptyRoute, emptyState));

      expect(router.serializeUrl(result as UrlTree)).toBe('/access-denied');
    });
  });
});

/**
 * The guard specs above only build a UrlTree, so they stayed green while
 * /access-denied had no route at all and every denial threw NG04002.
 */
describe('redirect targets', () => {
  beforeEach(() => {
    TestBed.resetTestingModule();
    TestBed.configureTestingModule({ providers: [provideRouter(routes)] });
  });

  it('lands on /access-denied rather than falling through to the wildcard', async () => {
    const router = TestBed.inject(Router);

    await router.navigateByUrl('/access-denied');

    expect(router.url).toBe('/access-denied');
  });

  it('resolves an unknown URL instead of throwing', async () => {
    await expect(TestBed.inject(Router).navigateByUrl('/no-such-page')).resolves.toBe(true);
  });
});
