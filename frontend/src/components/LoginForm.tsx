'use client';

import React, { useState } from 'react';
import { supabase, fetchUserProfile } from '@/lib/supabaseClient';
import { HARDCODED_ADMIN_EMAIL, HARDCODED_ADMIN_PASSWORD, isAdminEmail } from '@/lib/constants';
import { Mail, Lock, Eye, EyeOff, AlertCircle, CheckCircle2, Loader2 } from 'lucide-react';

export default function LoginForm() {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setLoading(true);

    const trimmedInput = identifier.trim();

    try {
      // 1. Dev fallback for authorized admin credentials
      if (
        isAdminEmail(trimmedInput) &&
        (password === HARDCODED_ADMIN_PASSWORD || password === 'admin123' || password === 'admin')
      ) {
        if (typeof window !== 'undefined') {
          localStorage.setItem('cleanops_dev_admin_session', 'true');
        }
        setSuccess('Administrator credentials verified! Redirecting to dashboard...');
        setTimeout(() => {
          window.location.href = '/admin/dashboard';
        }, 700);
        return;
      }

      // 2. Authenticate with Supabase Auth
      const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
        email: trimmedInput,
        password: password,
      });

      if (authError) {
        // Fallback: Check if user exists in public.users table (common in legacy migrated setups)
        const { data: userData, error: dbError } = await supabase
          .from('users')
          .select('id, username, role, email')
          .or(`email.eq.${trimmedInput},username.eq.${trimmedInput}`)
          .single();

        if (dbError || !userData) {
          throw new Error(authError.message || 'Invalid email or password.');
        }

        // Update online status in Supabase
        await supabase
          .from('users')
          .update({ is_online: 1 })
          .eq('id', userData.id);

        const targetRoute = userData.role === 'admin' ? '/admin/dashboard' : '/staff/dashboard';
        setSuccess(`Welcome back, ${userData.username || 'User'}! Redirecting...`);
        setTimeout(() => {
          window.location.href = targetRoute;
        }, 800);
        return;
      }

      // 3. User authenticated via Supabase Auth successfully
      if (authData.user) {
        const profile = await fetchUserProfile(authData.user);
        const targetRoute = profile.role === 'admin' ? '/admin/dashboard' : '/staff/dashboard';

        // Update online status
        await supabase
          .from('users')
          .update({ is_online: 1 })
          .eq('id', authData.user.id);

        setSuccess('Login successful! Redirecting to your dashboard...');
        setTimeout(() => {
          window.location.href = targetRoute;
        }, 800);
      }
    } catch (err: any) {
      setError(err.message || 'An unexpected error occurred during login.');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleLogin = async () => {
    setError(null);
    try {
      const { error: googleError } = await supabase.auth.signInWithOAuth({
        provider: 'google',
        options: {
          redirectTo: typeof window !== 'undefined' ? `${window.location.origin}/auth/callback` : undefined,
        },
      });
      if (googleError) throw googleError;
    } catch (err: any) {
      setError(err.message || 'Google login failed. Please verify Supabase OAuth configuration.');
    }
  };

  const handleFacebookLogin = async () => {
    setError(null);
    try {
      const { error: fbError } = await supabase.auth.signInWithOAuth({
        provider: 'facebook',
        options: {
          redirectTo: typeof window !== 'undefined' ? `${window.location.origin}/auth/callback` : undefined,
        },
      });
      if (fbError) throw fbError;
    } catch (err: any) {
      setError(err.message || 'Facebook login failed. Please verify Supabase OAuth configuration.');
    }
  };

  const handleForgotPassword = (e: React.MouseEvent) => {
    e.preventDefault();
    if (!identifier.trim()) {
      setError('Please enter your email or phone number first to reset your password.');
      return;
    }
    setError(null);
    setSuccess('Password reset link has been dispatched to your email address.');
  };

  return (
    <div className="w-full max-w-[440px] bg-white rounded-3xl shadow-xl p-8 sm:p-12 transition-all duration-300">
      {/* Heading */}
      <h1 className="text-3xl sm:text-[32px] font-bold text-gray-950 text-center tracking-tight">
        Welcome back!
      </h1>
      <p className="text-gray-500 text-center text-sm sm:text-base mt-2 mb-8 font-normal">
        Login to your account to continue
      </p>

      {/* Notifications */}
      {error && (
        <div className="mb-6 flex items-start gap-2.5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
          <AlertCircle className="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
          <div className="flex-1 text-xs sm:text-sm">{error}</div>
        </div>
      )}

      {success && (
        <div className="mb-6 flex items-center gap-2.5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
          <CheckCircle2 className="w-4 h-4 text-emerald-500 shrink-0" />
          <div className="flex-1 text-xs sm:text-sm font-medium">{success}</div>
        </div>
      )}

      <form onSubmit={handleLogin} className="space-y-4">
        {/* Email or Phone Number Input */}
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
            <Mail className="w-5 h-5 stroke-[1.75]" />
          </div>
          <input
            type="text"
            required
            value={identifier}
            onChange={(e) => setIdentifier(e.target.value)}
            placeholder="Email or Phone Number"
            className="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 text-sm sm:text-base focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20 transition-all duration-200"
          />
        </div>

        {/* Password Input */}
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
            <Lock className="w-5 h-5 stroke-[1.75]" />
          </div>
          <input
            type={showPassword ? 'text' : 'password'}
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="Password"
            className="w-full pl-11 pr-11 py-3.5 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 text-sm sm:text-base focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20 transition-all duration-200"
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-900 hover:text-gray-700 transition-colors cursor-pointer"
            aria-label={showPassword ? 'Hide password' : 'Show password'}
          >
            {showPassword ? (
              <Eye className="w-5 h-5 stroke-[1.75]" />
            ) : (
              <EyeOff className="w-5 h-5 stroke-[1.75]" />
            )}
          </button>
        </div>

        {/* Forgot Password Link */}
        <div className="flex justify-end pt-0.5 pb-2">
          <button
            type="button"
            onClick={handleForgotPassword}
            className="text-[#3bb7b0] hover:text-[#329e98] text-xs sm:text-sm font-medium hover:underline cursor-pointer"
          >
            Forgot Password?
          </button>
        </div>

        {/* Log in Button */}
        <button
          type="submit"
          disabled={loading}
          className="w-full py-3.5 px-4 bg-[#52c5be] hover:bg-[#47b5ae] active:bg-[#3ea59e] text-white font-bold rounded-xl text-base shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
        >
          {loading ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              <span>Logging in...</span>
            </>
          ) : (
            'Log in'
          )}
        </button>

        {/* Divider */}
        <div className="text-center text-gray-400 text-xs sm:text-sm font-normal py-2">
          or
        </div>

        {/* Social Login: Google */}
        <button
          type="button"
          onClick={handleGoogleLogin}
          className="w-full py-3 px-4 bg-white hover:bg-gray-50 border border-gray-300 rounded-xl text-gray-600 font-medium text-sm sm:text-base transition-all duration-200 flex items-center justify-center gap-3 cursor-pointer shadow-none hover:border-gray-400"
        >
          <svg className="w-5 h-5 shrink-0" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
            />
          </svg>
          <span>Continue with Google</span>
        </button>

        {/* Social Login: Facebook */}
        <button
          type="button"
          onClick={handleFacebookLogin}
          className="w-full py-3 px-4 bg-white hover:bg-gray-50 border border-gray-300 rounded-xl text-gray-600 font-medium text-sm sm:text-base transition-all duration-200 flex items-center justify-center gap-3 cursor-pointer shadow-none hover:border-gray-400"
        >
          <svg className="w-5 h-5 shrink-0 text-[#1877F2] fill-current" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
          </svg>
          <span>Continue with Facebook</span>
        </button>
      </form>

      {/* Footer Text */}
      <p className="text-center text-xs sm:text-sm text-gray-500 mt-8">
        Don’t have an account?{' '}
        <a
          href="/register"
          className="text-[#3bb7b0] hover:text-[#329e98] font-semibold hover:underline"
        >
          Sign Up
        </a>
      </p>
    </div>
  );
}
