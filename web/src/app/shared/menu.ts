import { ChangeDetectionStrategy, Component, HostListener, signal } from '@angular/core';

/**
 * A small "more actions" dropdown, opened either by clicking its own trigger
 * button or by right-clicking the row/element that owns it (call `openAt`
 * from a `(contextmenu)` handler via a template reference variable).
 */
@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'app-menu',
  styleUrl: './menu.scss',
  templateUrl: './menu.html',
})
export class Menu {
  protected readonly isOpen = signal(false);
  protected readonly position = signal<{ left: number; top: number } | null>(null);

  @HostListener('document:click')
  protected onDocumentClick(): void {
    this.isOpen.set(false);
  }

  @HostListener('document:keydown.escape')
  protected onEscape(): void {
    this.isOpen.set(false);
  }

  protected toggle(event: MouseEvent): void {
    event.stopPropagation();
    this.position.set(null);
    this.isOpen.update((open) => !open);
  }

  /** Open anchored at the cursor — call from a `(contextmenu)` handler. */
  openAt(event: MouseEvent): void {
    event.preventDefault();
    event.stopPropagation();
    this.position.set({ left: event.clientX, top: event.clientY });
    this.isOpen.set(true);
  }

  close(): void {
    this.isOpen.set(false);
  }
}
