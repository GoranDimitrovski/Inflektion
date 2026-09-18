import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { SessionService } from '../../core/session';
import { LoginPage } from './login-page';

class FakeSession {
  readonly twoFactorPending = signal(false);
  readonly memberships = signal([{ account: { id: 7, name: 'Acme', slug: 'acme' }, role: 'owner', permissions: [] }]);

  login = vi.fn(async () => true);
  twoFactorChallenge = vi.fn(async () => true);
}

describe('LoginPage', () => {
  let fixture: ComponentFixture<LoginPage>;
  let session: FakeSession;
  let navigate: ReturnType<typeof vi.fn>;

  const setInput = (selector: string, value: string): void => {
    const input = fixture.nativeElement.querySelector(selector) as HTMLInputElement;
    input.value = value;
    input.dispatchEvent(new Event('input'));
    fixture.detectChanges();
  };

  const submitForm = async (): Promise<void> => {
    fixture.nativeElement.querySelector('form').dispatchEvent(new Event('submit', { cancelable: true }));
    await fixture.whenStable();
    fixture.detectChanges();
  };

  beforeEach(() => {
    session = new FakeSession();

    TestBed.configureTestingModule({
      providers: [provideRouter([]), { provide: SessionService, useValue: session }],
    });

    navigate = vi.spyOn(TestBed.inject(Router), 'navigate').mockResolvedValue(true) as unknown as ReturnType<typeof vi.fn>;

    fixture = TestBed.createComponent(LoginPage);
    fixture.detectChanges();
  });

  it('navigates to the first account after a successful login', async () => {
    setInput('input[type="email"]', 'ada@example.test');
    setInput('input[type="password"]', 'secret');

    await submitForm();

    expect(session.login).toHaveBeenCalledWith('ada@example.test', 'secret');
    expect(navigate).toHaveBeenCalledWith(['/accounts', 7, 'programs']);
  });

  it('shows an error when the credentials are rejected', async () => {
    session.login.mockResolvedValue(false);

    setInput('input[type="email"]', 'ada@example.test');
    setInput('input[type="password"]', 'wrong');
    await submitForm();

    expect(fixture.nativeElement.textContent).toContain('Invalid email or password.');
  });

  it('swaps to the challenge form instead of an error when 2FA is pending', async () => {
    // As the real service does: the flag is raised by the login call itself.
    session.login.mockImplementation(async () => {
      session.twoFactorPending.set(true);

      return false;
    });

    setInput('input[type="email"]', 'ada@example.test');
    setInput('input[type="password"]', 'secret');
    await submitForm();

    expect(fixture.nativeElement.textContent).toContain('Two-factor authentication');
    expect(fixture.nativeElement.textContent).not.toContain('Invalid email or password.');
  });

  it('sends a plain code as `code`', async () => {
    session.twoFactorPending.set(true);
    fixture.detectChanges();

    setInput('input[autocomplete="one-time-code"]', '123456');
    await submitForm();

    expect(session.twoFactorChallenge).toHaveBeenCalledWith({ code: '123456' });
    expect(navigate).toHaveBeenCalledWith(['/accounts', 7, 'programs']);
  });

  it('sends a hyphenated recovery code as `recovery_code`', async () => {
    session.twoFactorPending.set(true);
    fixture.detectChanges();

    setInput('input[autocomplete="one-time-code"]', 'abcde12345-fghij67890');
    await submitForm();

    expect(session.twoFactorChallenge).toHaveBeenCalledWith({ recovery_code: 'abcde12345-fghij67890' });
  });

  it('shows an error when the challenge is rejected', async () => {
    session.twoFactorPending.set(true);
    session.twoFactorChallenge.mockResolvedValue(false);
    fixture.detectChanges();

    setInput('input[autocomplete="one-time-code"]', '000000');
    await submitForm();

    expect(fixture.nativeElement.textContent).toContain('That code is invalid or has expired.');
    expect(navigate).not.toHaveBeenCalled();
  });
});
