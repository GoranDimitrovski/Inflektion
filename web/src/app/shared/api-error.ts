/**
 * JSON:API error documents shape errors as `{ errors: [{ detail }] }`; the
 * routes outside `/v1` (Fortify's 2FA endpoints, invitation accept, leave)
 * use Laravel's default `{ message }`. One reader covers both.
 */
export function firstApiError(error: unknown, fallback: string): string {
  const body = error as { errors?: { detail?: string }[]; message?: string } | undefined;

  return body?.errors?.[0]?.detail ?? body?.message ?? fallback;
}
