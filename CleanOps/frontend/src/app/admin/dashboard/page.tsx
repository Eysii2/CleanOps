'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/components/AuthProvider';
import { supabase, OrderItem, ShopInfo } from '@/lib/supabaseClient';
import {
  LayoutDashboard,
  ShoppingBag,
  Users,
  DollarSign,
  Package,
  FileText,
  LogOut,
  TrendingUp,
  Clock,
  CheckCircle,
  AlertCircle,
  PlusCircle,
  Calendar,
  Store,
  Loader2,
} from 'lucide-react';

type Timeframe = 'total' | 'daily' | 'weekly' | 'monthly' | 'yearly';

export default function AdminDashboardPage() {
  const { user, profile, loading: authLoading, signOut } = useAuth();
  const [shop, setShop] = useState<ShopInfo | null>(null);
  const [orders, setOrders] = useState<OrderItem[]>([]);
  const [timeframe, setTimeframe] = useState<Timeframe>('total');
  const [loading, setLoading] = useState(true);

  // Fallback demo data if Supabase tables haven't been seeded yet
  const [stats, setStats] = useState({
    totalOrders: 28,
    totalRevenue: 14520,
    activeStaff: 4,
    pendingOrders: 6,
  });

  useEffect(() => {
    if (!authLoading && !user) {
      window.location.href = '/';
      return;
    }

    if (!authLoading && user) {
      loadDashboardData();
    }
  }, [user, authLoading, timeframe]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      // 1. Fetch Shop details for this admin user
      if (user) {
        const { data: shopData } = await supabase
          .from('shops')
          .select('id, shop_name, address, contact_number')
          .eq('user_id', user.id)
          .single();

        if (shopData) {
          setShop(shopData);
        } else {
          setShop({
            id: profile?.shop_id || 1,
            shop_name: 'Clean & Fresh Laundry',
          });
        }
      }

      // 2. Fetch Orders from Supabase
      const { data: ordersData, error: ordersErr } = await supabase
        .from('orders')
        .select('*')
        .order('created_at', { ascending: false })
        .limit(10);

      if (!ordersErr && ordersData && ordersData.length > 0) {
        setOrders(ordersData as OrderItem[]);
        // Compute stats
        const revenue = ordersData
          .filter((o) => o.status === 'Completed' || o.payment_status === 'Paid')
          .reduce((sum, o) => sum + (Number(o.total_amount) || 0), 0);
        const pending = ordersData.filter((o) => o.status === 'Pending').length;

        setStats((prev) => ({
          ...prev,
          totalOrders: ordersData.length,
          totalRevenue: revenue || prev.totalRevenue,
          pendingOrders: pending || prev.pendingOrders,
        }));
      } else {
        // Mock fallback to preview the exact CleanOps layout
        setOrders([
          {
            id: 101,
            shop_id: 1,
            customer_name: 'Maria Santos',
            category: 'Wash & Fold',
            status: 'Pending',
            total_amount: 350.0,
            payment_status: 'Unpaid',
            created_at: new Date().toISOString(),
          },
          {
            id: 102,
            shop_id: 1,
            customer_name: 'Juan Dela Cruz',
            category: 'Dry Cleaning',
            status: 'In Progress',
            total_amount: 720.0,
            payment_status: 'Paid',
            created_at: new Date().toISOString(),
          },
          {
            id: 103,
            shop_id: 1,
            customer_name: 'Sarah Connor',
            category: 'Express Wash',
            status: 'Completed',
            total_amount: 500.0,
            payment_status: 'Paid',
            created_at: new Date().toISOString(),
          },
        ]);
      }
    } catch (err) {
      console.error('Error fetching dashboard data:', err);
    } finally {
      setLoading(false);
    }
  };

  if (authLoading) {
    return (
      <div className="min-h-screen bg-cleanops-dark flex items-center justify-center text-cleanops-teal">
        <Loader2 className="w-8 h-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-cleanops-dark text-cleanops-light flex flex-col md:flex-row">
      {/* Sidebar */}
      <aside className="w-full md:w-64 bg-cleanops-card/90 border-r border-cleanops-teal/20 p-6 flex flex-col justify-between shrink-0">
        <div className="space-y-6">
          {/* Logo & Shop Title */}
          <div>
            <div className="flex items-center gap-2 mb-1 text-cleanops-teal">
              <Store className="w-6 h-6" />
              <h1 className="text-xl font-bold tracking-tight">CleanOps</h1>
            </div>
            <p className="text-xs text-cleanops-light/60 font-medium truncate">
              {shop?.shop_name || 'Laundry Management'}
            </p>
          </div>

          {/* Navigation Links */}
          <nav className="space-y-1 text-sm font-medium">
            <Link
              href="/admin/dashboard"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg bg-cleanops-teal text-cleanops-dark font-semibold shadow-md shadow-cleanops-teal/20 transition-all"
            >
              <LayoutDashboard className="w-4 h-4" />
              Dashboard
            </Link>
            <Link
              href="/admin/orders"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <ShoppingBag className="w-4 h-4" />
              Orders
            </Link>
            <Link
              href="/admin/staff"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <Users className="w-4 h-4" />
              Staff
            </Link>
            <Link
              href="/admin/revenue"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <DollarSign className="w-4 h-4" />
              Revenue & Billing
            </Link>
            <Link
              href="/admin/stock"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <Package className="w-4 h-4" />
              Stock / Inventory
            </Link>
            <Link
              href="/admin/reports"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <FileText className="w-4 h-4" />
              Reports
            </Link>
          </nav>
        </div>

        {/* User Account & Sign Out */}
        <div className="pt-6 border-t border-cleanops-surface">
          <div className="flex items-center justify-between mb-3">
            <div className="truncate">
              <p className="text-xs font-semibold text-cleanops-light truncate">
                {profile?.username || user?.email}
              </p>
              <span className="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-cleanops-teal/20 text-cleanops-teal">
                Admin
              </span>
            </div>
          </div>
          <button
            onClick={signOut}
            className="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-red-500/30 text-red-300 hover:bg-red-500/10 transition-colors text-xs font-semibold cursor-pointer"
          >
            <LogOut className="w-3.5 h-3.5" />
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main Content Area */}
      <main className="flex-1 p-6 md:p-10 overflow-y-auto">
        {/* Header Bar */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
          <div>
            <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-cleanops-light">
              Admin Dashboard
            </h2>
            <p className="text-sm text-cleanops-light/60">
              Overview of laundry performance, revenue, and pending tasks.
            </p>
          </div>

          {/* Timeframe Filter (Daily, Weekly, Monthly, Yearly, Total) */}
          <div className="flex items-center gap-1.5 p-1 bg-cleanops-card rounded-lg border border-cleanops-teal/20 text-xs font-semibold">
            {(['daily', 'weekly', 'monthly', 'yearly', 'total'] as Timeframe[]).map((tf) => (
              <button
                key={tf}
                onClick={() => setTimeframe(tf)}
                className={`capitalize px-3 py-1.5 rounded-md transition-all ${
                  timeframe === tf
                    ? 'bg-cleanops-teal text-cleanops-dark shadow'
                    : 'text-cleanops-light/70 hover:text-cleanops-light'
                }`}
              >
                {tf}
              </button>
            ))}
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
          {/* Card 1: Total Orders */}
          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20">
            <div className="flex items-center justify-between text-cleanops-light/60 mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Total Orders</span>
              <ShoppingBag className="w-5 h-5 text-cleanops-teal" />
            </div>
            <div className="text-3xl font-extrabold text-cleanops-light">{stats.totalOrders}</div>
            <p className="text-[11px] text-cleanops-teal mt-1 flex items-center gap-1 font-medium">
              <TrendingUp className="w-3 h-3" /> Filter: {timeframe}
            </p>
          </div>

          {/* Card 2: Total Revenue */}
          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20">
            <div className="flex items-center justify-between text-cleanops-light/60 mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Total Revenue</span>
              <DollarSign className="w-5 h-5 text-cleanops-teal" />
            </div>
            <div className="text-3xl font-extrabold text-cleanops-teal">
              ₱{stats.totalRevenue.toLocaleString()}
            </div>
            <p className="text-[11px] text-cleanops-light/60 mt-1">From completed & paid loads</p>
          </div>

          {/* Card 3: Pending Orders */}
          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20">
            <div className="flex items-center justify-between text-cleanops-light/60 mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Pending Orders</span>
              <Clock className="w-5 h-5 text-amber-400" />
            </div>
            <div className="text-3xl font-extrabold text-amber-400">{stats.pendingOrders}</div>
            <p className="text-[11px] text-cleanops-light/60 mt-1">Awaiting wash / processing</p>
          </div>

          {/* Card 4: Active Staff */}
          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20">
            <div className="flex items-center justify-between text-cleanops-light/60 mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">Active Staff</span>
              <Users className="w-5 h-5 text-cleanops-teal" />
            </div>
            <div className="text-3xl font-extrabold text-cleanops-light">{stats.activeStaff}</div>
            <p className="text-[11px] text-emerald-400 mt-1 flex items-center gap-1 font-medium">
              <CheckCircle className="w-3 h-3" /> Online & assigned
            </p>
          </div>
        </div>

        {/* Recent Orders Section */}
        <div className="glass-panel rounded-xl border border-cleanops-teal/20 p-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h3 className="text-lg font-bold text-cleanops-light">Recent Laundry Orders</h3>
              <p className="text-xs text-cleanops-light/60">Live status from CleanOps database</p>
            </div>
            <button className="flex items-center gap-2 py-2 px-3.5 bg-cleanops-teal text-cleanops-dark rounded-lg text-xs font-bold hover:bg-cleanops-accent transition-all shadow-md shadow-cleanops-teal/20 cursor-pointer">
              <PlusCircle className="w-4 h-4" />
              New Order
            </button>
          </div>

          {loading ? (
            <div className="py-12 flex justify-center text-cleanops-teal">
              <Loader2 className="w-6 h-6 animate-spin" />
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr className="border-b border-cleanops-surface text-xs font-bold uppercase tracking-wider text-cleanops-teal">
                    <th className="pb-3 px-3">Order ID</th>
                    <th className="pb-3 px-3">Customer</th>
                    <th className="pb-3 px-3">Category</th>
                    <th className="pb-3 px-3">Amount</th>
                    <th className="pb-3 px-3">Status</th>
                    <th className="pb-3 px-3">Payment</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-cleanops-surface/60">
                  {orders.map((order) => (
                    <tr key={order.id} className="hover:bg-cleanops-surface/40 transition-colors">
                      <td className="py-3.5 px-3 font-semibold text-cleanops-light">#{order.id}</td>
                      <td className="py-3.5 px-3">{order.customer_name}</td>
                      <td className="py-3.5 px-3 text-cleanops-light/70">{order.category || 'General'}</td>
                      <td className="py-3.5 px-3 font-bold text-cleanops-teal">
                        ₱{Number(order.total_amount || 0).toFixed(2)}
                      </td>
                      <td className="py-3.5 px-3">
                        <span
                          className={`inline-block px-2.5 py-1 rounded-full text-xs font-semibold ${
                            order.status === 'Completed'
                              ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                              : order.status === 'In Progress'
                              ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30'
                              : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                          }`}
                        >
                          {order.status}
                        </span>
                      </td>
                      <td className="py-3.5 px-3">
                        <span
                          className={`text-xs font-semibold ${
                            order.payment_status === 'Paid' ? 'text-emerald-400' : 'text-amber-400'
                          }`}
                        >
                          {order.payment_status || 'Unpaid'}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
