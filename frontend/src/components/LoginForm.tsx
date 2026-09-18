'use client';

import React, { useState } from 'react';
import { supabase, UserRole } from '@/lib/supabaseClient';
import { Mail, Lock, Eye, EyeOff, AlertCircle, CheckCircle2, Loader2, ShieldCheck, UserCheck } from 'lucide-react';

export default function LoginForm() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState<UserRole>('admin');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setLoading(true);

    try {
      // 1. Authenticate with Supabase Auth
      const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
        email: email.trim(),
        password: password,
      });

      if (authError) {
        // Fallback: Check if user exists in public.users table (common in migrated PHP setups)
        const { data: userData, error: dbError } = await supabase
          .from('users')
          .select('id, username, role, shop_id')
          .eq('email', email.trim())
          .single();

        if (dbError || !userData) {
          throw new Error('No account found with that email, or invalid credentials.');
        }

        // Validate selected role against database record
        if (userData.role !== role) {
          throw new Error('Access Denied: You selected the wrong role.');
        }

        // Update online status in Supabase
        await supabase
          .from('users')
          .update({ is_online: 1 })
          .eq('id', userData.id);

        setSuccess(`Welcome back, ${userData.username || 'User'}! Redirecting...`);
        setTimeout(() => {
          window.location.href = userData.role === 'admin' ? '/admin/dashboard' : '/staff/dashboard';
        }, 1200);
        return;
      }

      // If Supabase Auth succeeded:
      const authUser = authData.user;
      const userRole = authUser.user_metadata?.role || role;

      if (userRole !== role) {
        throw new Error('Access Denied: You selected the wrong role.');
      }

      // Update online status in public.users table if it exists
      await supabase
        .from('users')
        .update({ is_online: 1 })
        .eq('id', authUser.id);

      setSuccess(`Authentication successful! Redirecting to ${role} dashboard...`);
      setTimeout(() => {
        window.location.href = role === 'admin' ? '/admin/dashboard' : '/staff/dashboard';
      }, 1200);

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
      setError(err.message || 'Google login failed. Please check your Supabase OAuth settings.');
    }
  };

  return (
    <div className="login-card glass-panel w-full max-w-md p-8 md:p-10 rounded-2xl transition-all duration-300">
      <div className="text-center mb-8">
        <h2 className="text-2xl md:text-3xl font-bold tracking-tight text-cleanops-teal mb-2">
          MALAKING BURAT
        </h2>
        <p className="text-cleanops-light/70 text-sm font-medium">
          Turning daily loads into organized growth
        </p>
      </div>

      {error && (
        <div className="mb-6 flex items-start gap-3 p-3.5 rounded-lg bg-red-950/50 border border-red-500/40 text-red-200 text-sm animate-fadeIn">
          <AlertCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
          <div className="flex-1">{error}</div>
        </div>
      )}

      {success && (
        <div className="mb-6 flex items-center gap-3 p-3.5 rounded-lg bg-emerald-950/50 border border-emerald-500/40 text-emerald-200 text-sm animate-fadeIn">
          <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
          <div className="flex-1 font-medium">{success}</div>
        </div>
      )}

      <form onSubmit={handleLogin} className="space-y-5">
        {/* Email Input */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-2">
            Email Address
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <Mail className="w-4 h-4" />
            </div>
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="shop@example.com"
              className="w-full pl-10 pr-4 py-3 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all duration-200 text-sm"
            />
          </div>
        </div>

        {/* Password Input */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-2">
            Password
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <Lock className="w-4 h-4" />
            </div>
            <input
              type={showPassword ? 'text' : 'password'}
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              className="w-full pl-10 pr-11 py-3 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all duration-200 text-sm"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-cleanops-light/40 hover:text-cleanops-teal transition-colors"
            >
              {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
            </button>
          </div>
        </div>

        {/* Role Selector */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-2">
            Login As
          </label>
          <div className="grid grid-cols-2 gap-3 p-1 bg-cleanops-surface/60 rounded-xl border border-cleanops-teal/20">
            <button
              type="button"
              onClick={() => setRole('admin')}
              className={`flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200 ${role === 'admin'
                ? 'bg-cleanops-teal text-cleanops-dark shadow-md shadow-cleanops-teal/20'
                : 'text-cleanops-light/70 hover:text-cleanops-light hover:bg-cleanops-surface'
                }`}
            >
              <ShieldCheck className="w-4 h-4" />
              Admin
            </button>

            <button
              type="button"
              onClick={() => setRole('staff')}
              className={`flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg text-sm font-semibold transition-all duration-200 ${role === 'staff'
                ? 'bg-cleanops-teal text-cleanops-dark shadow-md shadow-cleanops-teal/20'
                : 'text-cleanops-light/70 hover:text-cleanops-light hover:bg-cleanops-surface'
                }`}
            >
              <UserCheck className="w-4 h-4" />
              Staff
            </button>
          </div>
        </div>

        {/* Login Button */}
        <button
          type="submit"
          disabled={loading}
          className="w-full py-3 px-4 bg-cleanops-teal hover:bg-cleanops-accent active:scale-[0.99] text-cleanops-dark font-bold rounded-lg shadow-lg shadow-cleanops-teal/25 hover:shadow-cleanops-teal/40 transition-all duration-200 flex items-center justify-center gap-2 text-sm disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer"
        >
          {loading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              <span>Verifying credentials...</span>
            </>
          ) : (
            'Login'
          )}
        </button>

        {/* Divider */}
        <div className="flex items-center my-4 text-xs text-cleanops-light/50 uppercase tracking-widest">
          <div className="flex-1 border-t border-cleanops-surface"></div>
          <span className="px-3">OR</span>
          <div className="flex-1 border-t border-cleanops-surface"></div>
        </div>

        {/* Google Login Button */}
        <button
          type="button"
          onClick={handleGoogleLogin}
          className="w-full py-3 px-4 bg-transparent hover:bg-cleanops-teal/10 border border-cleanops-teal/40 hover:border-cleanops-teal text-cleanops-light hover:text-cleanops-accent font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-3 text-sm cursor-pointer"
        >
          <i className="fa-brands fa-google text-cleanops-teal"></i>
          <span>Continue with Google</span>
        </button>
      </form>

      {/* Footer Text (Dynamic: Hidden when Staff is selected, matching legacy behavior) */}
      {role === 'admin' && (
        <p className="mt-8 text-center text-xs text-cleanops-light/70 transition-opacity duration-300">
          Don&apos;t have an account?{' '}
          <a
            href="/register"
            className="text-cleanops-teal hover:text-cleanops-accent font-semibold underline underline-offset-4 hover:decoration-cleanops-accent transition-colors"
          >
            Register here
          </a>
        </p>
      )}
    </div>
  );
}
