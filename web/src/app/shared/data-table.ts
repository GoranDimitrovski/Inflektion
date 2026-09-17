import { ChangeDetectionStrategy, Component, input } from '@angular/core';

/** Wraps the four states any server-backed list needs (loading/error/empty/rows); the caller projects its own `<table>` markup. */
@Component({
  changeDetection: ChangeDetectionStrategy.OnPush,
  selector: 'app-data-table',
  styleUrl: './data-table.scss',
  templateUrl: './data-table.html',
})
export class DataTable {
  readonly loading = input(false);
  readonly error = input<string | null>(null);
  readonly empty = input(false);
  readonly emptyMessage = input('No results.');
}
