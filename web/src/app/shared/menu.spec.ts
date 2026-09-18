import { Component, viewChild } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Menu } from './menu';

@Component({
  imports: [Menu],
  template: `
    <div class="row" (contextmenu)="menu().openAt($event)">
      <app-menu>
        <button type="button" class="menu-item">Copy redirect URL</button>
      </app-menu>
    </div>
  `,
})
class HostComponent {
  readonly menu = viewChild.required(Menu);
}

describe('Menu', () => {
  let fixture: ComponentFixture<HostComponent>;

  const panel = (): HTMLElement | null => fixture.nativeElement.querySelector('.menu-panel');
  const trigger = (): HTMLElement => fixture.nativeElement.querySelector('.icon-button');

  beforeEach(() => {
    TestBed.configureTestingModule({ imports: [HostComponent] });
    fixture = TestBed.createComponent(HostComponent);
    fixture.detectChanges();
  });

  it('starts closed', () => {
    expect(panel()).toBeNull();
  });

  it('opens on the trigger and projects its items', () => {
    trigger().click();
    fixture.detectChanges();

    expect(panel()).not.toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Copy redirect URL');
  });

  it('closes when the trigger is clicked again', () => {
    trigger().click();
    fixture.detectChanges();
    trigger().click();
    fixture.detectChanges();

    expect(panel()).toBeNull();
  });

  it('closes on a click elsewhere in the document', () => {
    trigger().click();
    fixture.detectChanges();

    document.dispatchEvent(new MouseEvent('click'));
    fixture.detectChanges();

    expect(panel()).toBeNull();
  });

  it('closes on Escape', () => {
    trigger().click();
    fixture.detectChanges();

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    fixture.detectChanges();

    expect(panel()).toBeNull();
  });

  it('opens at the cursor on right-click and suppresses the native menu', () => {
    const event = new MouseEvent('contextmenu', { clientX: 120, clientY: 240, cancelable: true, bubbles: true });
    fixture.nativeElement.querySelector('.row').dispatchEvent(event);
    fixture.detectChanges();

    expect(event.defaultPrevented).toBe(true);
    expect(panel()).not.toBeNull();
    expect(panel()!.classList).toContain('fixed');
    expect(panel()!.style.left).toBe('120px');
    expect(panel()!.style.top).toBe('240px');
  });

  it('close() dismisses an open panel', () => {
    trigger().click();
    fixture.detectChanges();

    fixture.componentInstance.menu().close();
    fixture.detectChanges();

    expect(panel()).toBeNull();
  });
});
