import { TestBed } from '@angular/core/testing';
import { LinksFacade } from './links.facade';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const program = { type: 'programs', id: '1', attributes: { name: 'Acme', slug: 'acme' } };

const link = {
  type: 'links',
  id: '1',
  attributes: {
    programId: 1,
    destinationUrl: 'https://example.test/landing',
    token: 'abc123',
    redirectUrl: 'http://localhost:8000/r/abc123',
    status: 'active',
    personalizationStrategy: null,
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-01T00:00:00Z',
  },
};

describe('LinksFacade', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    TestBed.configureTestingModule({ providers: [LinksFacade] });
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('loadPrograms populates the program list', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: [program] }));

    const facade = TestBed.inject(LinksFacade);
    await facade.loadPrograms(5);

    expect(facade.programs()).toEqual([program]);
  });

  it('loadLinks populates the link list for the given program', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: [link] }));

    const facade = TestBed.inject(LinksFacade);
    await facade.loadLinks(5, 1);

    expect(facade.links()).toEqual([link]);
    expect(facade.error()).toBeNull();
    const [request] = fetchMock.mock.calls[0] as [Request];
    expect(request.url).toContain('filter[programId]=1');
  });

  it('loadLinks sets an error message on failure', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Account not found.' }] }, 404));

    const facade = TestBed.inject(LinksFacade);
    await facade.loadLinks(5, 1);

    expect(facade.error()).toBe('Account not found.');
  });

  it('create prepends the created link and returns null on success', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: link }));

    const facade = TestBed.inject(LinksFacade);
    const result = await facade.create(5, { programId: 1, destinationUrl: 'https://example.test/landing' });

    expect(result).toBeNull();
    expect(facade.links()).toEqual([link]);
  });

  it('create returns the server-provided detail message on failure', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'The destination URL is invalid.' }] }, 422));

    const facade = TestBed.inject(LinksFacade);
    const result = await facade.create(5, { programId: 1, destinationUrl: 'not-a-url' });

    expect(result).toBe('The destination URL is invalid.');
    expect(facade.links()).toEqual([]);
  });

  it('updateStatus replaces the link with the updated one', async () => {
    const updated = { ...link, attributes: { ...link.attributes, status: 'paused' } };
    fetchMock
      .mockResolvedValueOnce(jsonResponse({ data: link }))
      .mockResolvedValueOnce(jsonResponse({ data: updated }));

    const facade = TestBed.inject(LinksFacade);
    await facade.create(5, { programId: 1, destinationUrl: 'https://example.test/landing' });
    await facade.updateStatus(5, link.id, 'paused');

    expect(facade.links()).toEqual([updated]);
  });
});
