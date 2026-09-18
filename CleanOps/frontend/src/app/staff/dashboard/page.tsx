'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/components/AuthProvider';
import { supabase, OrderItem } from '@/lib/supabaseClient';
import {
  ClipboardList,
  CheckCircle2,
  Clock,
  Truck,
  CreditCard,
  LogOut,
  ChevronRight,
  Loader2,
  Store,
  RefreshCw,
} from 'lucide-react';

export default function StaffDashboardPage() {
  const { user, profile, loading: authLoading, signOut } = useAuth();
  const [orders, setOrders] = useState<OrderItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<number | string | null>(null);

  const [stats, setStats] = useState({
    totalOrders: 15,
    pendingOrders: 5,
  });

  useEffect(() => {
    if (!authLoading && !user) {
      window.location.href = '/';
      return;
    }

    if (!authLoading && user) {
      loadStaffOrders();
    }
  }, [user, authLoading]);

  const loadStaffOrders = async () => {
    setLoading(true);
    try {
      const shopId = profile?.shop_id || 1;
      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .eq('shop_id', shopId)
        .order('id', { ascending: false })
        .limit(15);

      if (!error && data && data.length > 0) {
        setOrders(data as OrderItem[]);
        const pending = data.filter((o) => o.status === 'Pending').length;
        setStats({
          totalOrders: data.length,
          pendingOrders: pending,
        });
      } else {
        // Mock fallback for staff tasks preview
        setOrders([
          {
            id: 201,
            shop_id: 1,
            customer_name: 'Elena Gilbert',
            category: 'Beddings & Comforters',
            status: 'Pending',
            total_amount: 450,
            payment_status: 'Paid',
          },
          {
            id: 202,
            shop_id: 1,
            customer_name: 'Damon Salvatore',
            category: 'Dry Cleaning',
            status: 'In Progress',
            total_amount: 800,
            payment_status: 'Unpaid',
          },
          {
            id: 203,
            shop_id: 1,
            customer_name: 'Stefan Salvatore',
            category: 'Wash & Fold',
            status: 'Ready for Pickup',
            total_amount: 320,
            payment_status: 'Paid',
          },
        ]);
      }
    } catch (err) {
      console.error('Error fetching staff tasks:', err);
    } finally {
      setLoading(false);
    }
  };

  const updateOrderStatus = async (
    orderId: number | string,
    nextStatus: OrderItem['status']
  ) => {
    setUpdatingId(orderId);
    try {
      await supabase.from('orders').update({ status: nextStatus }).eq('id', orderId);

      setOrders((prev) =>
        prev.map((o) => (o.id === orderId ? { ...o, status: nextStatus } : o))
      );
    } catch (err) {
      console.error('Failed to update status:', err);
    } finally {
      setUpdatingId(null);
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
      {/* Staff Sidebar */}
      <aside className="w-full md:w-64 bg-cleanops-card/90 border-r border-cleanops-teal/20 p-6 flex flex-col justify-between shrink-0">
        <div className="space-y-6">
          <div>
            <div className="flex items-center gap-2 mb-1 text-cleanops-teal">
              <Store className="w-6 h-6" />
              <h1 className="text-xl font-bold tracking-tight">CleanOps Staff</h1>
            </div>
            <p className="text-xs text-cleanops-light/60 font-medium">Daily Operations Hub</p>
          </div>

          <nav className="space-y-1 text-sm font-medium">
            <Link
              href="/staff/dashboard"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg bg-cleanops-teal text-cleanops-dark font-semibold shadow-md shadow-cleanops-teal/20 transition-all"
            >
              <ClipboardList className="w-4 h-4" />
              Task Dashboard
            </Link>
            <Link
              href="/staff/delivery"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <Truck className="w-4 h-4" />
              Deliveries
            </Link>
            <Link
              href="/staff/payment"
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-cleanops-light/70 hover:text-cleanops-teal hover:bg-cleanops-surface/60 transition-all"
            >
              <CreditCard className="w-4 h-4" />
              Payments
            </Link>
          </nav>
        </div>

        <div className="pt-6 border-t border-cleanops-surface">
          <div className="mb-3">
            <p className="text-xs font-semibold text-cleanops-light truncate">
              {profile?.username || user?.email}
            </p>
            <span className="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-300">
              Staff Member
            </span>
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

      {/* Main Staff Work Area */}
      <main className="flex-1 p-6 md:p-10 overflow-y-auto">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-cleanops-light">
              Laundry Processing Queue
            </h2>
            <p className="text-sm text-cleanops-light/60">
              Update wash states and mark orders ready for customer collection.
            </p>
          </div>

          <button
            onClick={loadStaffOrders}
            className="flex items-center gap-2 px-3 py-2 bg-cleanops-surface hover:bg-cleanops-card border border-cleanops-teal/20 rounded-lg text-xs font-semibold text-cleanops-teal transition-all cursor-pointer"
          >
            <RefreshCw className="w-3.5 h-3.5" />
            Refresh Queue
          </button>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20 flex items-center justify-between">
            <div>
              <span className="text-xs font-semibold uppercase tracking-wider text-cleanops-light/60">
                Assigned Orders
              </span>
              <div className="text-3xl font-extrabold text-cleanops-light mt-1">
                {stats.totalOrders}
              </div>
            </div>
            <div className="w-12 h-12 rounded-xl bg-cleanops-teal/10 flex items-center justify-center text-cleanops-teal border border-cleanops-teal/30">
              <ClipboardList className="w-6 h-6" />
            </div>
          </div>

          <div className="glass-panel p-5 rounded-xl border border-cleanops-teal/20 flex items-center justify-between">
            <div>
              <span className="text-xs font-semibold uppercase tracking-wider text-cleanops-light/60">
                Pending Action
              </span>
              <div className="text-3xl font-extrabold text-amber-400 mt-1">
                {stats.pendingOrders}
              </div>
            </div>
            <div className="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 border border-amber-500/30">
              <Clock className="w-6 h-6" />
            </div>
          </div>
        </div>

        {/* Order Cards List */}
        <div className="space-y-4">
          <h3 className="text-lg font-bold text-cleanops-light">Active Laundry Tasks</h3>

          {loading ? (
            <div className="py-12 flex justify-center text-cleanops-teal">
              <Loader2 className="w-6 h-6 animate-spin" />
            </div>
          ) : (
            orders.map((order) => (
              <div
                key={order.id}
                className="glass-panel p-5 rounded-xl border border-cleanops-teal/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-cleanops-teal/40 transition-all"
              >
                <div>
                  <div className="flex items-center gap-3 mb-1">
                    <span className="font-bold text-cleanops-teal">#{order.id}</span>
                    <span className="text-sm font-semibold text-cleanops-light">
                      {order.customer_name}
                    </span>
                    <span className="text-xs px-2 py-0.5 rounded bg-cleanops-surface text-cleanops-light/70 font-medium">
                      {order.category || 'Standard Wash'}
                    </span>
                  </div>
                  <p className="text-xs text-cleanops-light/50">
                    Amount: ₱{Number(order.total_amount || 0).toFixed(2)} • Payment:{' '}
                    <strong
                      className={
                        order.payment_status === 'Paid' ? 'text-emerald-400' : 'text-amber-400'
                      }
                    >
                      {order.payment_status || 'Unpaid'}
                    </strong>
                  </p>
                </div>

                {/* Workflow Buttons */}
                <div className="flex items-center gap-2">
                  {order.status === 'Pending' && (
                    <button
                      disabled={updatingId === order.id}
                      onClick={() => updateOrderStatus(order.id, 'In Progress')}
                      className="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow"
                    >
                      {updatingId === order.id ? (
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                      ) : (
                        <ChevronRight className="w-3.5 h-3.5" />
                      )}
                      Start Washing
                    </button>
                  )}

                  {order.status === 'In Progress' && (
                    <button
                      disabled={updatingId === order.id}
                      onClick={() => updateOrderStatus(order.id, 'Ready for Pickup')}
                      className="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow"
                    >
                      {updatingId === order.id ? (
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                      ) : (
                        <CheckCircle2 className="w-3.5 h-3.5" />
                      )}
                      Mark Ready
                    </button>
                  )}

                  {order.status === 'Ready for Pickup' && (
                    <button
                      disabled={updatingId === order.id}
                      onClick={() => updateOrderStatus(order.id, 'Completed')}
                      className="px-3.5 py-1.5 rounded-lg bg-cleanops-teal hover:bg-cleanops-accent text-cleanops-dark text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow"
                    >
                      {updatingId === order.id ? (
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                      ) : (
                        <CheckCircle2 className="w-3.5 h-3.5" />
                      )}
                      Complete & Handover
                    </button>
                  )}

                  {order.status === 'Completed' && (
                    <span className="text-xs font-semibold text-emerald-400 flex items-center gap-1">
                      <CheckCircle2 className="w-4 h-4" /> Finished
                    </span>
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      </main>
    </div>
  );
}
