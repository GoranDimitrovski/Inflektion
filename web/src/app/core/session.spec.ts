import { TestBed } from '@angular/core/testing';
import { SessionService } from './session';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const oneMembership = {
  data: {
    user: { id: 1, name: 'Ada', email: 'ada@example.test' },
    memberships: [
      {
        account: { id: 5, name: 'Acme', slug: 'acme' },
        role: 'owner',
        permissions: ['programs.read', 'programs.write'],
      },
    ],
  },
};

describe('SessionService', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC';
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('starts unauthenticated with no permissions', () => {
    const session = TestBed.inject(SessionService);

    expect(session.isAuthenticated()).toBe(false);
    expect(session.permissions()).toEqual([]);
  });

  it('loads the user and memberships on a successful login', async () => {
    fetchMock
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(oneMembership));

    const session = TestBed.inject(SessionService);
    const success = await session.login('ada@example.test', 'password');

    expect(success).toBe(true);
    expect(session.isAuthenticated()).toBe(true);
    expect(session.user()).toEqual({ id: 1, name: 'Ada', email: 'ada@example.test' });
    expect(session.memberships()).toHaveLength(1);
  });

  it('returns false and never calls /me when login fails', async () => {
    fetchMock.mockResolvedValueOnce(new Response(null, { status: 422 }));

    const session = TestBed.inject(SessionService);
    const success = await session.login('ada@example.test', 'wrong-password');

    expect(success).toBe(false);
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(session.isAuthenticated()).toBe(false);
  });

  it('sets twoFactorPending and never calls /me when the account has 2FA enabled', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: { twoFactorRequired: true } }, 200));

    const session = TestBed.inject(SessionService);
    const success = await session.login('ada@example.test', 'password');

    expect(success).toBe(false);
    expect(session.twoFactorPending()).toBe(true);
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(session.isAuthenticated()).toBe(false);
  });

  it('twoFactorChallenge completes the login and clears twoFactorPending on success', async () => {
    fetchMock
      .mockResolvedValueOnce(jsonResponse({ data: { twoFactorRequired: true } }, 200))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(oneMembership));

    const session = TestBed.inject(SessionService);
    await session.login('ada@example.test', 'password');

    const success = await session.twoFactorChallenge({ code: '123456' });

    expect(success).toBe(true);
    expect(session.twoFactorPending()).toBe(false);
    expect(session.isAuthenticated()).toBe(true);
  });

  it('twoFactorChallenge returns false on an invalid code', async () => {
    fetchMock.mockResolvedValueOnce(new Response(null, { status: 422 }));

    const session = TestBed.inject(SessionService);

    expect(await session.twoFactorChallenge({ code: 'wrong' })).toBe(false);
  });

  it('resolves permissions from the membership matching the active account, not any membership', async () => {
    fetchMock.mockResolvedValueOnce(
      jsonResponse({
        data: {
          user: { id: 1, name: 'Ada', email: 'ada@example.test' },
          memberships: [
            { account: { id: 5, name: 'Acme', slug: 'acme' }, role: 'owner', permissions: ['programs.read', 'programs.write'] },
            { account: { id: 6, name: 'Other', slug: 'other' }, role: 'viewer', permissions: ['programs.read'] },
          ],
        },
      }),
    );

    const session = TestBed.inject(SessionService);
    await session.load();

    session.setActiveAccountId(6);
    expect(session.can('programs.write')).toBe(false);

    session.setActiveAccountId(5);
    expect(session.can('programs.write')).toBe(true);
  });

  it('clears user, memberships, and active account on logout', async () => {
    fetchMock
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(oneMembership))
      .mockResolvedValueOnce(new Response(null, { status: 204 }));

    const session = TestBed.inject(SessionService);
    await session.login('ada@example.test', 'password');
    session.setActiveAccountId(5);

    await session.logout();

    expect(session.isAuthenticated()).toBe(false);
    expect(session.memberships()).toEqual([]);
    expect(session.activeMembership()).toBeNull();
  });

  it('requestPasswordReset resolves true on a successful response', async () => {
    fetchMock.mockResolvedValueOnce(new Response(null, { status: 204 }));

    const session = TestBed.inject(SessionService);

    expect(await session.requestPasswordReset('ada@example.test')).toBe(true);
  });

  it('requestPasswordReset resolves false on a failed response, without throwing', async () => {
    fetchMock.mockResolvedValueOnce(new Response(null, { status: 429 }));

    const session = TestBed.inject(SessionService);

    expect(await session.requestPasswordReset('ada@example.test')).toBe(false);
  });

  it('resetPassword posts the token, email, and new password', async () => {
    fetchMock.mockResolvedValueOnce(new Response(null, { status: 204 }));

    const session = TestBed.inject(SessionService);
    const success = await session.resetPassword({
      token: 'a-token',
      email: 'ada@example.test',
      password: 'new-password',
      password_confirmation: 'new-password',
    });

    expect(success).toBe(true);
    const [url, init] = fetchMock.mock.calls[0] as [string, RequestInit];
    expect(url).toContain('/reset-password');
    expect(JSON.parse(init.body as string)).toEqual({
      token: 'a-token',
      email: 'ada@example.test',
      password: 'new-password',
      password_confirmation: 'new-password',
    });
  });

  it('sends the decoded XSRF-TOKEN cookie as a header on unsafe requests', async () => {
    document.cookie = 'XSRF-TOKEN=abc%3Ddef';
    fetchMock
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(oneMembership));

    const session = TestBed.inject(SessionService);
    await session.login('ada@example.test', 'password');

    const [, init] = fetchMock.mock.calls[0] as [string, RequestInit];

    expect((init.headers as Record<string, string>)['X-XSRF-TOKEN']).toBe('abc=def');
  });
});
