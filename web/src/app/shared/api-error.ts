/** JSON:API error documents shape errors as `{ errors: [{ detail }] }`, not Laravel's default `{ errors: { field: [...] } }`. */
export function firstApiError(error: unknown, fallback: string): string {
  const errors = (error as { errors?: { detail?: string }[] } | undefined)?.errors;

  return errors?.[0]?.detail ?? fallback;
}
