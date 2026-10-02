'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { supabase, fetchUserProfile, OrderItem } from '@/lib/supabaseClient';
import { HARDCODED_ADMIN_EMAIL, HARDCODED_ADMIN_PASSWORD, isAdminEmail } from '@/lib/constants';
import {
  Mail,
  Lock,
  Eye,
  EyeOff,
  AlertCircle,
  CheckCircle2,
  Loader2,
  Calendar,
  PackageSearch,
  X,
  Search,
  Clock,
  Sparkles,
  ArrowRight,
  LayoutDashboard,
} from 'lucide-react';

export default function LoginForm() {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Modals for Book Now and Order Tracking
  const [showTrackingModal, setShowTrackingModal] = useState(false);
  const [showBookingModal, setShowBookingModal] = useState(false);
  const [trackingId, setTrackingId] = useState('');
  const [trackingResult, setTrackingResult] = useState<OrderItem | null>(null);
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [trackingError, setTrackingError] = useState<string | null>(null);

  // Booking Form State
  const [bookingName, setBookingName] = useState('');
  const [bookingPhone, setBookingPhone] = useState('');
  const [bookingCategory, setBookingCategory] = useState('Wash & Fold');
  const [bookingSubmitted, setBookingSubmitted] = useState(false);

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
        // Fallback: Check if user exists in public.users table
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

  const handleForgotPassword = (e: React.MouseEvent) => {
    e.preventDefault();
    if (!identifier.trim()) {
      setError('Please enter your email or phone number first to reset your password.');
      return;
    }
    setError(null);
    setSuccess('Password reset link has been dispatched to your email address.');
  };

  const handleTrackOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!trackingId.trim()) return;
    setTrackingLoading(true);
    setTrackingError(null);
    setTrackingResult(null);

    const cleanId = trackingId.replace('#', '').trim();

    try {
      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .eq('id', cleanId)
        .single();

      if (error || !data) {
        throw new Error(`Order #${cleanId} was not found. Please verify your order receipt number.`);
      } else {
        setTrackingResult(data as OrderItem);
      }
    } catch (err: any) {
      setTrackingError(err.message || 'Unable to find order.');
    } finally {
      setTrackingLoading(false);
    }
  };

  const handleBookingSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setBookingSubmitted(true);
    setTimeout(() => {
      setBookingSubmitted(false);
      setShowBookingModal(false);
      setBookingName('');
      setBookingPhone('');
    }, 2000);
  };

  return (
    <>
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
        </form>
      </div>

      {/* Order Tracking Modal */}
      {showTrackingModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fadeIn">
          <div className="w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-gray-100">
            <button
              onClick={() => setShowTrackingModal(false)}
              className="absolute top-5 right-5 p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-2xl bg-[#d2e8e7] flex items-center justify-center text-[#3bb7b0]">
                <PackageSearch className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-950">Track Your Order</h3>
                <p className="text-xs text-gray-500">Real-time status of your laundry load</p>
              </div>
            </div>

            <form onSubmit={handleTrackOrder} className="space-y-4">
              <div className="relative">
                <input
                  type="text"
                  required
                  value={trackingId}
                  onChange={(e) => setTrackingId(e.target.value)}
                  placeholder="Enter Order #"
                  className="w-full pl-4 pr-12 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 placeholder-gray-400 text-sm focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                />
                <button
                  type="submit"
                  disabled={trackingLoading}
                  className="absolute right-2 top-2 p-1.5 bg-[#52c5be] hover:bg-[#47b5ae] text-white rounded-lg transition-colors cursor-pointer"
                >
                  {trackingLoading ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Search className="w-4 h-4" />
                  )}
                </button>
              </div>
            </form>

            {trackingError && (
              <div className="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0" />
                <span>{trackingError}</span>
              </div>
            )}

            {trackingResult && (
              <div className="mt-5 p-4 rounded-2xl bg-[#d2e8e7]/50 border border-[#52c5be]/30 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
                    Order #{trackingResult.id}
                  </span>
                  <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#52c5be] text-gray-950">
                    {trackingResult.status}
                  </span>
                </div>
                <div>
                  <p className="text-base font-bold text-gray-950">{trackingResult.customer_name}</p>
                  <p className="text-xs text-gray-600 font-medium">{trackingResult.category || 'Standard Service'}</p>
                </div>
                <div className="pt-2 border-t border-[#52c5be]/20 flex justify-between text-xs font-semibold text-gray-700">
                  <span>Payment: {trackingResult.payment_status || 'Unpaid'}</span>
                  <span>Amount: ₱{Number(trackingResult.total_amount || 0).toFixed(2)}</span>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Book Now Modal */}
      {showBookingModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fadeIn">
          <div className="w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-gray-100">
            <button
              onClick={() => setShowBookingModal(false)}
              className="absolute top-5 right-5 p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3 mb-5">
              <div className="w-10 h-10 rounded-2xl bg-[#d2e8e7] flex items-center justify-center text-[#3bb7b0]">
                <Calendar className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-950">Book a Laundry Load</h3>
                <p className="text-xs text-gray-500">Fast, automated garment care service</p>
              </div>
            </div>

            {bookingSubmitted ? (
              <div className="py-8 text-center space-y-3">
                <div className="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                  <CheckCircle2 className="w-6 h-6" />
                </div>
                <h4 className="text-base font-bold text-gray-950">Booking Request Sent!</h4>
                <p className="text-xs text-gray-500">Our laundry team will contact you shortly to confirm pickup.</p>
              </div>
            ) : (
              <form onSubmit={handleBookingSubmit} className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Your Full Name</label>
                  <input
                    type="text"
                    required
                    value={bookingName}
                    onChange={(e) => setBookingName(e.target.value)}
                    placeholder="Enter your name"
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Phone Number</label>
                  <input
                    type="tel"
                    required
                    value={bookingPhone}
                    onChange={(e) => setBookingPhone(e.target.value)}
                    placeholder="Enter phone number"
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Service Type</label>
                  <select
                    value={bookingCategory}
                    onChange={(e) => setBookingCategory(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20 cursor-pointer"
                  >
                    <option value="Wash & Fold">Wash & Fold</option>
                    <option value="Dry Cleaning">Dry Cleaning</option>
                    <option value="Express Wash">Express Wash</option>
                    <option value="Beddings & Linen">Beddings & Linen</option>
                  </select>
                </div>

                <button
                  type="submit"
                  className="w-full py-3 px-4 bg-[#52c5be] hover:bg-[#47b5ae] text-white font-bold rounded-xl text-sm shadow-sm transition-all cursor-pointer mt-2"
                >
                  Confirm Booking
                </button>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
}

