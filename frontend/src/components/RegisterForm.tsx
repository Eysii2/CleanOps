'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { supabase } from '@/lib/supabaseClient';
import {
  Store,
  User,
  MapPin,
  Phone,
  Mail,
  Lock,
  Eye,
  EyeOff,
  AlertCircle,
  CheckCircle2,
  Loader2,
  Sparkles,
} from 'lucide-react';

export default function RegisterForm() {
  const [shopName, setShopName] = useState('');
  const [username, setUsername] = useState('');
  const [address, setAddress] = useState('');
  const [contact, setContact] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);
    setLoading(true);

    try {
      // 1. Sign up user via Supabase Auth with metadata
      const { data: authData, error: authError } = await supabase.auth.signUp({
        email: email.trim(),
        password: password,
        options: {
          data: {
            username: username.trim(),
            role: 'admin',
            shop_name: shopName.trim(),
          },
        },
      });

      if (authError) {
        throw authError;
      }

      const createdUser = authData.user;
      if (!createdUser) {
        throw new Error('Registration failed. Please try again.');
      }

      // 2. Create record in public.users table (if available)
      try {
        await supabase.from('users').upsert({
          id: createdUser.id,
          username: username.trim(),
          email: email.trim(),
          role: 'admin',
          is_online: 1,
        });
      } catch (userDbErr) {
        console.warn('Could not write to public.users directly:', userDbErr);
      }

      // 3. Create shop record in public.shops
      try {
        await supabase.from('shops').insert({
          user_id: createdUser.id,
          shop_name: shopName.trim(),
          address: address.trim(),
          contact_number: contact.trim(),
        });
      } catch (shopDbErr) {
        console.warn('Could not write to public.shops directly:', shopDbErr);
      }

      setSuccess('Shop registered successfully! Redirecting to login...');
      setTimeout(() => {
        window.location.href = '/';
      }, 1500);

    } catch (err: any) {
      setError(err.message || 'An error occurred during registration. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleSignup = async () => {
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
      setError(err.message || 'Google registration failed. Please try again.');
    }
  };

  return (
    <div className="w-full max-w-lg glass-panel p-8 sm:p-10 rounded-2xl shadow-2xl transition-all duration-300">
      <div className="text-center mb-8">
        <div className="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-cleanops-teal/10 text-cleanops-teal border border-cleanops-teal/30 mb-3">
          <Sparkles className="w-6 h-6" />
        </div>
        <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-cleanops-teal mb-2">
          Register Your Laundry Shop
        </h2>
        <p className="text-cleanops-light/70 text-sm font-medium">
          Enter your business information to get started with CleanOps
        </p>
      </div>

      {error && (
        <div className="mb-6 flex items-start gap-3 p-3.5 rounded-lg bg-red-950/50 border border-red-500/40 text-red-200 text-sm">
          <AlertCircle className="w-5 h-5 text-red-400 shrink-0 mt-0.5" />
          <div className="flex-1">{error}</div>
        </div>
      )}

      {success && (
        <div className="mb-6 flex items-center gap-3 p-3.5 rounded-lg bg-emerald-950/50 border border-emerald-500/40 text-emerald-200 text-sm">
          <CheckCircle2 className="w-5 h-5 text-emerald-400 shrink-0" />
          <div className="flex-1 font-medium">{success}</div>
        </div>
      )}

      <form onSubmit={handleRegister} className="space-y-4">
        {/* Shop Name */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
            Shop Name
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <Store className="w-4 h-4" />
            </div>
            <input
              type="text"
              required
              value={shopName}
              onChange={(e) => setShopName(e.target.value)}
              placeholder="Clean & Fresh Laundry Services"
              className="w-full pl-10 pr-4 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
            />
          </div>
        </div>

        {/* Username */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
            Owner / Admin Username
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <User className="w-4 h-4" />
            </div>
            <input
              type="text"
              required
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder="arielle_admin"
              className="w-full pl-10 pr-4 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
            />
          </div>
        </div>

        {/* Address */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
            Shop Address
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <MapPin className="w-4 h-4" />
            </div>
            <input
              type="text"
              required
              value={address}
              onChange={(e) => setAddress(e.target.value)}
              placeholder="123 Rizal Ave, Manila"
              className="w-full pl-10 pr-4 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
            />
          </div>
        </div>

        {/* Contact Number & Email in Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
              Contact Number
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
                <Phone className="w-4 h-4" />
              </div>
              <input
                type="tel"
                required
                value={contact}
                onChange={(e) => setContact(e.target.value)}
                placeholder="09123456789"
                className="w-full pl-10 pr-4 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
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
                className="w-full pl-10 pr-4 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
              />
            </div>
          </div>
        </div>

        {/* Password */}
        <div>
          <label className="block text-xs font-semibold uppercase tracking-wider text-cleanops-teal mb-1.5">
            Password
          </label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-cleanops-light/40">
              <Lock className="w-4 h-4" />
            </div>
            <input
              type={showPassword ? 'text' : 'password'}
              required
              minLength={6}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="•••••••• (Min. 6 characters)"
              className="w-full pl-10 pr-11 py-2.5 bg-cleanops-surface/80 border border-cleanops-surface rounded-lg text-cleanops-light placeholder-cleanops-light/40 glow-focus transition-all text-sm"
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

        {/* Submit Button */}
        <button
          type="submit"
          disabled={loading}
          className="w-full py-3 px-4 mt-2 bg-cleanops-teal hover:bg-cleanops-accent active:scale-[0.99] text-cleanops-dark font-bold rounded-lg shadow-lg shadow-cleanops-teal/25 hover:shadow-cleanops-teal/40 transition-all flex items-center justify-center gap-2 text-sm disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer"
        >
          {loading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              <span>Creating your shop account...</span>
            </>
          ) : (
            'Register Shop'
          )}
        </button>

        {/* Divider */}
        <div className="flex items-center my-4 text-xs text-cleanops-light/50 uppercase tracking-widest">
          <div className="flex-1 border-t border-cleanops-surface"></div>
          <span className="px-3">OR</span>
          <div className="flex-1 border-t border-cleanops-surface"></div>
        </div>

        {/* Google Signup */}
        <button
          type="button"
          onClick={handleGoogleSignup}
          className="w-full py-2.5 px-4 bg-transparent hover:bg-cleanops-teal/10 border border-cleanops-teal/40 hover:border-cleanops-teal text-cleanops-light hover:text-cleanops-accent font-semibold rounded-lg transition-all flex items-center justify-center gap-3 text-sm cursor-pointer"
        >
          <i className="fa-brands fa-google text-cleanops-teal"></i>
          <span>Continue with Google</span>
        </button>
      </form>

      <p className="mt-6 text-center text-xs text-cleanops-light/70">
        Already have an account?{' '}
        <Link
          href="/"
          className="text-cleanops-teal hover:text-cleanops-accent font-semibold underline underline-offset-4 hover:decoration-cleanops-accent transition-colors"
        >
          Login here
        </Link>
      </p>
    </div>
  );
}
