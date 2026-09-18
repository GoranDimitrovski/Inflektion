import { TestBed } from '@angular/core/testing';
import { SessionService } from '../../core/session';
import { MembersFacade } from './members.facade';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const membership = {
  type: 'memberships',
  id: '1',
  attributes: { userId: 1, userName: 'Ada', userEmail: 'ada@example.test', role: 'owner' },
};

const invitation = {
  type: 'invitations',
  id: '2',
  attributes: { email: 'grace@example.test', role: 'member', status: 'pending' },
};

describe('MembersFacade', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [MembersFacade, { provide: SessionService, useValue: { requireAccountId: () => 5 } }],
    });
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  describe('load', () => {
    it('loads memberships and pending invitations together', async () => {
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ data: [membership] }))
        .mockResolvedValueOnce(jsonResponse({ data: [invitation] }));

      const facade = TestBed.inject(MembersFacade);
      await facade.load();

      expect(facade.memberships()).toEqual([membership]);
      expect(facade.invitations()).toEqual([invitation]);
      expect(facade.error()).toBeNull();
    });

    it('sets an error message if either request fails', async () => {
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Account not found.' }] }, 404))
        .mockResolvedValueOnce(jsonResponse({ data: [] }));

      const facade = TestBed.inject(MembersFacade);
      await facade.load();

      expect(facade.error()).toBe('Account not found.');
    });
  });

  describe('invite', () => {
    it('prepends the created invitation and returns null on success', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ data: invitation }));

      const facade = TestBed.inject(MembersFacade);
      const result = await facade.invite('grace@example.test', 'member');

      expect(result).toBeNull();
      expect(facade.invitations()).toEqual([invitation]);
    });

    it('returns the server-provided detail message on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Already invited.' }] }, 422));

      const facade = TestBed.inject(MembersFacade);
      const result = await facade.invite('grace@example.test', 'member');

      expect(result).toBe('Already invited.');
      expect(facade.invitations()).toEqual([]);
    });
  });

  describe('revokeInvitation', () => {
    it('removes the invitation from the list on success', async () => {
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ data: invitation }))
        .mockResolvedValueOnce(new Response(null, { status: 204 }));

      const facade = TestBed.inject(MembersFacade);
      await facade.invite('grace@example.test', 'member');

      expect(await facade.revokeInvitation(invitation.id)).toBe(true);
      expect(facade.invitations()).toEqual([]);
    });

    it('returns false and leaves the list untouched on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({}, 500));

      const facade = TestBed.inject(MembersFacade);
      expect(await facade.revokeInvitation(invitation.id)).toBe(false);
    });
  });

  describe('changeRole', () => {
    it('replaces the membership with the updated one on success', async () => {
      const updated = { ...membership, attributes: { ...membership.attributes, role: 'admin' } };
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ data: [membership] }))
        .mockResolvedValueOnce(jsonResponse({ data: [] }))
        .mockResolvedValueOnce(jsonResponse({ data: updated }));

      const facade = TestBed.inject(MembersFacade);
      await facade.load();
      const result = await facade.changeRole(membership.id, 'admin');

      expect(result).toBeNull();
      expect(facade.memberships()).toEqual([updated]);
    });

    it('returns the server-provided detail message on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Cannot demote the last owner.' }] }, 422));

      const facade = TestBed.inject(MembersFacade);
      const result = await facade.changeRole(membership.id, 'member');

      expect(result).toBe('Cannot demote the last owner.');
    });
  });

  describe('revokeMembership', () => {
    it('removes the membership from the list on success', async () => {
      fetchMock
        .mockResolvedValueOnce(jsonResponse({ data: [membership] }))
        .mockResolvedValueOnce(jsonResponse({ data: [] }))
        .mockResolvedValueOnce(new Response(null, { status: 204 }));

      const facade = TestBed.inject(MembersFacade);
      await facade.load();
      const result = await facade.revokeMembership(membership.id);

      expect(result).toBeNull();
      expect(facade.memberships()).toEqual([]);
    });

    it('returns the server-provided detail message on failure', async () => {
      fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Cannot remove the last owner.' }] }, 422));

      const facade = TestBed.inject(MembersFacade);
      const result = await facade.revokeMembership(membership.id);

      expect(result).toBe('Cannot remove the last owner.');
    });
  });
});
