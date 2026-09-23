/**
 * CleanOps Admin & System Constants
 */

export const HARDCODED_ADMIN_EMAIL = 'admin@cleanops.com';
export const HARDCODED_ADMIN_PASSWORD = 'admin';

export const AUTHORIZED_ADMIN_EMAILS: readonly string[] = [
  'admin@cleanops.com',
  'jasminesarion04@gmail.com',
];

/**
 * Check if a given email is recognized as an authorized admin email
 */
export function isAdminEmail(email?: string | null): boolean {
  if (!email) return false;
  const trimmed = email.trim().toLowerCase();
  return AUTHORIZED_ADMIN_EMAILS.map((e) => e.toLowerCase()).includes(trimmed);
}
