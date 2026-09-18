import { TestBed } from '@angular/core/testing';
import { SessionService } from '../../core/session';
import { SettingsFacade } from './settings.facade';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const token = {
  type: 'api-tokens',
  id: '1',
  attributes: { name: 'CI', abilities: ['programs.read'], lastUsedAt: null, expiresAt: null, createdAt: '2026-01-01T00:00:00Z' },
};

describe('SettingsFacade', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [SettingsFacade, { provide: SessionService, useValue: { requireAccountId: () => 5 } }],
    });
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  describe('loadApiTokens', () => {
    it('loads the token list', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ data: [token] }));

      const facade = TestBed.inject(SettingsFacade);
      await facade.loadApiTokens();

      expect(facade.apiTokens()).toEqual([token]);
      expect(facade.tokensError()).toBeNull();
    });

    it('sets an error message on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Account not found.' }] }, 404));

      const facade = TestBed.inject(SettingsFacade);
      await facade.loadApiTokens();

      expect(facade.tokensError()).toBe('Account not found.');
    });
  });

  describe('createApiToken', () => {
    it('prepends the created token and returns the plaintext token', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ data: token, meta: { plainTextToken: 'plain-secret' } }));

      const facade = TestBed.inject(SettingsFacade);
      const result = await facade.createApiToken('CI', ['programs.read']);

      expect(result).toEqual({ token, plainTextToken: 'plain-secret' });
      expect(facade.apiTokens()).toEqual([token]);
    });

    it('returns the server-provided detail message on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Cannot grant abilities you do not have.' }] }, 422));

      const facade = TestBed.inject(SettingsFacade);
      const result = await facade.createApiToken('CI', ['members.manage']);

      expect(result).toEqual({ error: 'Cannot grant abilities you do not have.' });
      expect(facade.apiTokens()).toEqual([]);
    });
  });

  describe('revokeApiToken', () => {
    it('removes the token from the list on success', async () => {
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ data: token, meta: { plainTextToken: 'plain-secret' } }))
        .mockResolvedValueOnce(new Response(null, { status: 204 }));

      const facade = TestBed.inject(SettingsFacade);
      await facade.createApiToken('CI', []);

      expect(await facade.revokeApiToken(token.id)).toBe(true);
      expect(facade.apiTokens()).toEqual([]);
    });

    it('returns false on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({}, 500));

      const facade = TestBed.inject(SettingsFacade);

      expect(await facade.revokeApiToken(token.id)).toBe(false);
    });
  });

  describe('startEnrollment', () => {
    it('fetches the QR code and secret key after enabling', async () => {
      fetchMock
        .mockResolvedValueOnce(new Response(null, { status: 204 }))
        .mockResolvedValueOnce(jsonResponse({ data: { svg: '<svg></svg>', url: 'otpauth://...' } }))
        .mockResolvedValueOnce(jsonResponse({ data: { secretKey: 'SECRET123' } }));

      const facade = TestBed.inject(SettingsFacade);
      const started = await facade.startEnrollment();

      expect(started).toBe(true);
      expect(facade.qrCodeSvg()).toBe('<svg></svg>');
      expect(facade.secretKey()).toBe('SECRET123');
    });

    it('sets an error and does not fetch the QR code on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'Two-factor authentication is already enabled.' }, 422));

      const facade = TestBed.inject(SettingsFacade);
      const started = await facade.startEnrollment();

      expect(started).toBe(false);
      expect(facade.twoFactorError()).toBe('Two-factor authentication is already enabled.');
      expect(fetchMock).toHaveBeenCalledTimes(1);
    });
  });

  describe('confirmEnrollment', () => {
    it('clears the QR/secret and loads recovery codes on success', async () => {
      fetchMock
        .mockResolvedValueOnce(new Response(null, { status: 204 }))
        .mockResolvedValueOnce(jsonResponse({ data: ['code-one', 'code-two'] }));

      const facade = TestBed.inject(SettingsFacade);
      const confirmed = await facade.confirmEnrollment('123456');

      expect(confirmed).toBe(true);
      expect(facade.qrCodeSvg()).toBeNull();
      expect(facade.recoveryCodes()).toEqual(['code-one', 'code-two']);
    });

    it('sets an error on an invalid code', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'The provided two-factor code was invalid.' }, 422));

      const facade = TestBed.inject(SettingsFacade);
      const confirmed = await facade.confirmEnrollment('000000');

      expect(confirmed).toBe(false);
      expect(facade.twoFactorError()).toBe('The provided two-factor code was invalid.');
    });
  });

  describe('disableTwoFactor', () => {
    it('returns null and clears recovery codes on success', async () => {
      fetchMock.mockResolvedValueOnce(new Response(null, { status: 204 }));

      const facade = TestBed.inject(SettingsFacade);

      expect(await facade.disableTwoFactor()).toBeNull();
      expect(facade.recoveryCodes()).toEqual([]);
    });

    it('returns the server-provided message when disabling is refused', async () => {
      fetchMock.mockResolvedValueOnce(
        jsonResponse({ message: 'Owners must keep two-factor authentication enabled.' }, 422),
      );

      const facade = TestBed.inject(SettingsFacade);

      expect(await facade.disableTwoFactor()).toBe('Owners must keep two-factor authentication enabled.');
    });
  });
});
