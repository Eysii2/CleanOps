import { createClient, User, Session } from '@supabase/supabase-js';

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

export interface ShopInfo {
  id: number | string;
  user_id?: string;
  shop_name: string;
  address?: string;
  contact_number?: string;
}

export interface OrderItem {
  id: number | string;
  shop_id: number | string;
  customer_name: string;
  category?: string;
  status: 'Pending' | 'In Progress' | 'Ready for Pickup' | 'Completed' | 'Cancelled';
  total_amount?: number;
  payment_status?: 'Paid' | 'Unpaid';
  created_at?: string;
}

// Helper: Get active profile from public.users or metadata
export async function fetchUserProfile(user: User): Promise<UserProfile> {
  const metaRole = (user.user_metadata?.role as UserRole) || 'admin';
  const metaUsername = user.user_metadata?.username || user.email?.split('@')[0] || 'User';

  try {
    const { data, error } = await supabase
      .from('users')
      .select('id, email, username, role, shop_id, is_online')
      .eq('id', user.id)
      .single();

    if (!error && data) {
      return {
        id: data.id,
        email: data.email || user.email,
        username: data.username || metaUsername,
        role: (data.role as UserRole) || metaRole,
        shop_id: data.shop_id,
        is_online: data.is_online,
      };
    }
  } catch {
    // Fallback to auth metadata if public.users query is blocked or table is empty
  }

  return {
    id: user.id,
    email: user.email,
    username: metaUsername,
    role: metaRole,
  };
}

// Helper: Sign out and reset online status
export async function signOutUser(userId?: string) {
  if (userId) {
    try {
      await supabase.from('users').update({ is_online: 0 }).eq('id', userId);
    } catch {
      // ignore
    }
  }
  return supabase.auth.signOut();
}
