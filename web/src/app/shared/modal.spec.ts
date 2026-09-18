import { Component, signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Modal } from './modal';

@Component({
  imports: [Modal],
  template: `
    <app-modal [open]="open()" title="New program" (closed)="closed = closed + 1">
      <p class="body">projected content</p>
    </app-modal>
  `,
})
class HostComponent {
  readonly open = signal(true);
  closed = 0;
}

describe('Modal', () => {
  let fixture: ComponentFixture<HostComponent>;
  let host: HostComponent;

  beforeEach(() => {
    TestBed.configureTestingModule({ imports: [HostComponent] });
    fixture = TestBed.createComponent(HostComponent);
    host = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('renders the title and projected content when open', () => {
    expect(fixture.nativeElement.querySelector('.dialog')).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('New program');
    expect(fixture.nativeElement.querySelector('.body')).not.toBeNull();
  });

  it('renders nothing when closed', () => {
    host.open.set(false);
    fixture.detectChanges();

    expect(fixture.nativeElement.querySelector('.dialog')).toBeNull();
    expect(fixture.nativeElement.querySelector('.body')).toBeNull();
  });

  it('emits closed when the close button is clicked', () => {
    fixture.nativeElement.querySelector('.icon-button').click();

    expect(host.closed).toBe(1);
  });

  it('emits closed when the backdrop itself is clicked', () => {
    fixture.nativeElement.querySelector('.backdrop').click();

    expect(host.closed).toBe(1);
  });

  it('does not emit closed when the click originates inside the dialog', () => {
    fixture.nativeElement.querySelector('.body').click();

    expect(host.closed).toBe(0);
  });

  it('emits closed on Escape while open', () => {
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

    expect(host.closed).toBe(1);
  });

  it('ignores Escape while closed', () => {
    host.open.set(false);
    fixture.detectChanges();

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

    expect(host.closed).toBe(0);
  });
});
