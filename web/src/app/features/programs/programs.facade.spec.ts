import { TestBed } from '@angular/core/testing';
import { SessionService } from '../../core/session';
import { ProgramsFacade } from './programs.facade';

function jsonResponse(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}

const program = {
  type: 'programs',
  id: '1',
  attributes: { name: 'Acme Affiliates', slug: 'acme-affiliates' },
};

describe('ProgramsFacade', () => {
  let fetchMock: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [ProgramsFacade, { provide: SessionService, useValue: { requireAccountId: () => 5 } }],
    });
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('loads programs into the list', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: [program] }));

    const facade = TestBed.inject(ProgramsFacade);
    await facade.load();

    expect(facade.programs()).toEqual([program]);
    expect(facade.loading()).toBe(false);
    expect(facade.error()).toBeNull();
  });

  it('sets the server-provided detail message when loading fails', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Account not found.' }] }, 404));

    const facade = TestBed.inject(ProgramsFacade);
    await facade.load();

    expect(facade.programs()).toEqual([]);
    expect(facade.error()).toBe('Account not found.');
  });

  it('falls back to a generic message when the load error has no detail', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({}, 500));

    const facade = TestBed.inject(ProgramsFacade);
    await facade.load();

    expect(facade.error()).toBe('Failed to load programs.');
  });

  it('prepends a newly created program to the list', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: program }));

    const facade = TestBed.inject(ProgramsFacade);
    const created = await facade.create({ name: 'Acme Affiliates', slug: 'acme-affiliates' });

    expect(created).toBe(true);
    expect(facade.programs()).toEqual([program]);
    expect(facade.error()).toBeNull();
  });

  it('returns false and sets an error message when creation fails, without touching the list', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ errors: [{ detail: 'Slug already taken.' }] }, 422));

    const facade = TestBed.inject(ProgramsFacade);
    const created = await facade.create({ name: 'Acme Affiliates', slug: 'acme-affiliates' });

    expect(created).toBe(false);
    expect(facade.programs()).toEqual([]);
    expect(facade.error()).toBe('Slug already taken.');
  });
});
