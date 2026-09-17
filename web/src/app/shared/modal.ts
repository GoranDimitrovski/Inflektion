import { ChangeDetectionStrategy, Component, HostListener, input, output } from '@angular/core';

/** A simple, dismissible dialog overlay. Content is projected; the caller owns the open/close signal. */
@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'app-modal',
  styleUrl: './modal.scss',
  templateUrl: './modal.html',
})
export class Modal {
  readonly open = input(false);
  readonly title = input('');
  readonly closed = output<void>();

  @HostListener('document:keydown.escape')
  protected onEscape(): void {
    if (this.open()) {
      this.closed.emit();
    }
  }

  protected onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.closed.emit();
    }
  }
}
