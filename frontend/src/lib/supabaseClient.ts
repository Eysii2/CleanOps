import { createClient } from '@supabase/supabase-js';

const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL || 'https://ocyojmaobggmkarjuglo.supabase.co';
const supabaseAnonKey = process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY || '';

if (!process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY) {
  console.warn(
    'Warning: NEXT_PUBLIC_SUPABASE_ANON_KEY is not defined in .env.local. Please provide your Supabase public anon key.'
  );
}

// Single Supabase Client instance to be used across the application
export const supabase = createClient(supabaseUrl, supabaseAnonKey);

export type UserRole = 'admin' | 'staff' | 'supervisor';

export interface UserProfile {
  id: string;
  email?: string;
  username?: string;
  role: UserRole;
  shop_id?: number | string | null;
  is_online?: boolean | number;
}
