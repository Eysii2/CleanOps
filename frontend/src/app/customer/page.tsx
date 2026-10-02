'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { supabase } from '@/lib/supabaseClient';
import {
  Phone,
  Sparkles,
  CheckCircle2,
  Clock,
  Package,
  MapPin,
  Calendar,
  CreditCard,
  ChevronRight,
  Search,
  Loader2,
  ArrowRight,
  ShieldCheck,
  AlertCircle,
  RefreshCw,
  Star,
  Receipt,
  User,
  Check,
  Layers,
  Truck,
  Droplets,
  Wind,
} from 'lucide-react';

interface ServiceOption {
  id: string;
  name: string;
  category: string;
  rate: number;
  unit: string;
  turnaround: string;
  desc: string;
  icon: string;
}

const SERVICES: ServiceOption[] = [
  {
    id: 'wash-fold',
    name: 'Wash & Fold',
    category: 'Everyday Clothes',
    rate: 35.0,
    unit: 'kg',
    turnaround: '24 Hours',
    desc: 'Deep clean, tumble dry, and neatly folded everyday apparel.',
    icon: '🧺',
  },
  {
    id: 'express',
    name: 'Express Wash & Dry',
    category: 'Rush Service',
    rate: 55.0,
    unit: 'kg',
    turnaround: '4 Hours',
    desc: 'Priority queue for when you need your clothes today.',
    icon: '⚡',
  },
  {
    id: 'dry-clean',
    name: 'Dry Cleaning',
    category: 'Suits & Delicates',
    rate: 120.0,
    unit: 'pc',
    turnaround: '48 Hours',
    desc: 'Gentle solvent care for coats, suits, blazers, and barongs.',
    icon: '👔',
  },
  {
    id: 'bedding',
    name: 'Beddings & Comforter',
    category: 'Heavy Linens',
    rate: 80.0,
    unit: 'pc',
    turnaround: '24 Hours',
    desc: 'Sanitizing cycle for thick comforters, blankets, and duvets.',
    icon: '🛏️',
  },
  {
    id: 'ironing',
    name: 'Steam Press / Ironing',
    category: 'Finishing',
    rate: 25.0,
    unit: 'pc',
    turnaround: '12 Hours',
    desc: 'Wrinkle-free high-temp steam press for crisp, sharp garments.',
    icon: '💨',
  },
];

interface CustomerOrder {
  id: number | string;
  order_number?: string;
  customer_name: string;
  category?: string;
  total_amount?: number;
  status: string;
  payment_status?: string;
  payment_method?: string;
  created_at?: string;
  notes?: string;
}

export default function CustomerWebsitePage() {
  const [activeTab, setActiveTab] = useState<'order' | 'track' | 'services'>('order');

  // Customer Identity State
  const [phone, setPhone] = useState('');
  const [fullName, setFullName] = useState('');
  const [address, setAddress] = useState('');
  const [deliveryType, setDeliveryType] = useState<'dropoff' | 'pickup'>('dropoff');
  const [isReturningCustomer, setIsReturningCustomer] = useState(false);
  const [isSearchingPhone, setIsSearchingPhone] = useState(false);

  // Order Selection State
  const [selectedService, setSelectedService] = useState<ServiceOption>(SERVICES[0]);
  const [quantity, setQuantity] = useState<number>(5);
  const [paymentMethod, setPaymentMethod] = useState<'Cash' | 'GCash' | 'Maya'>('Cash');
  const [specialInstructions, setSpecialInstructions] = useState('');
  const [pickupDate, setPickupDate] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().split('T')[0];
  });
  const [pickupTimeSlot, setPickupTimeSlot] = useState('Morning (8:00 AM - 12:00 PM)');

  // Form Submission State
  const [submitting, setSubmitting] = useState(false);
  const [submittedOrder, setSubmittedOrder] = useState<CustomerOrder | null>(null);
  const [orderError, setOrderError] = useState<string | null>(null);

  // Tracking by Phone State
  const [trackPhone, setTrackPhone] = useState('');
  const [customerOrders, setCustomerOrders] = useState<CustomerOrder[]>([]);
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [trackingSearched, setTrackingSearched] = useState(false);

  // Calculations
  const serviceSubtotal = selectedService.rate * quantity;
  const deliveryFee = deliveryType === 'pickup' ? 50 : 0;
  const grandTotal = serviceSubtotal + deliveryFee;

  // Auto-detect customer profile when phone number reaches standard length
  useEffect(() => {
    const cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.length >= 10) {
      lookupCustomerByPhone(cleaned);
    }
  }, [phone]);

  const lookupCustomerByPhone = async (phoneNumber: string) => {
    setIsSearchingPhone(true);
    try {
      // 1. Try finding in customers table
      const { data: custData } = await supabase
        .from('customers')
        .select('fullname, address, phone')
        .ilike('phone', `%${phoneNumber.slice(-10)}%`)
        .limit(1)
        .maybeSingle();

      if (custData) {
        if (!fullName) setFullName(custData.fullname);
        if (!address && custData.address) setAddress(custData.address);
        setIsReturningCustomer(true);
        setIsSearchingPhone(false);
        return;
      }

      // 2. Fallback: check recent orders with this phone
      const { data: orderData } = await supabase
        .from('orders')
        .select('customer_name, notes')
        .or(`customer_name.ilike.%${phoneNumber.slice(-10)}%,notes.ilike.%${phoneNumber.slice(-10)}%`)
        .order('created_at', { ascending: false })
        .limit(1)
        .maybeSingle();

      if (orderData) {
        // Strip phone from customer_name if formatted as "Name (Phone)"
        const cleanName = orderData.customer_name.replace(/\s*\([^)]*\)/, '').trim();
        if (!fullName && cleanName) setFullName(cleanName);
        setIsReturningCustomer(true);
      }
    } catch {
      // ignore lookup issues
    } finally {
      setIsSearchingPhone(false);
    }
  };

  // Handle Order Submit
  const handlePlaceOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    setOrderError(null);

    const cleanPhone = phone.trim();
    if (!cleanPhone) {
      setOrderError('Please provide your phone number so we can identify your load.');
      return;
    }
    if (!fullName.trim()) {
      setOrderError('Please enter your full name.');
      return;
    }

    setSubmitting(true);

    const orderNumber = `ORD-${Date.now().toString().slice(-6)}`;
    const fullCustomerName = `${fullName.trim()} (${cleanPhone})`;

    const notesPayload = [
      `Phone: ${cleanPhone}`,
      deliveryType === 'pickup' ? `Doorstep Pickup at: ${address}` : 'Drop-off at Shop',
      `Est. Load: ${quantity} ${selectedService.unit}`,
      `Preferred Slot: ${pickupDate} - ${pickupTimeSlot}`,
      specialInstructions.trim() ? `Instructions: ${specialInstructions.trim()}` : '',
    ]
      .filter(Boolean)
      .join(' | ');

    try {
      // 1. Insert order into public.orders (let DB auto-generate id)
      const { data: insertedOrder, error: orderInsertErr } = await supabase
        .from('orders')
        .insert([
          {
            order_number: orderNumber,
            shop_id: 1,
            customer_name: fullCustomerName,
            category: selectedService.name,
            total_amount: grandTotal,
            status: 'Pending',
            payment_status: 'Unpaid',
            payment_method: paymentMethod,
            order_source: 'customer_web',
            notes: notesPayload,
          },
        ])
        .select()
        .single();

      if (orderInsertErr) {
        console.error('Order insert error:', orderInsertErr);
        setOrderError(`Failed to place order: ${orderInsertErr.message}`);
        setSubmitting(false);
        return;
      }

      const savedOrderId = insertedOrder?.id || orderNumber;

      // 2. Save customer record (simple insert, skip if already exists)
      const { data: existingCustomer } = await supabase
        .from('customers')
        .select('id')
        .eq('phone', cleanPhone)
        .maybeSingle();

      if (!existingCustomer) {
        await supabase.from('customers').insert([
          {
            fullname: fullName.trim(),
            phone: cleanPhone,
            address: address.trim() || 'N/A',
            shop_id: 1,
          },
        ]);
      }

      // 3. Send notification alert to Admin/Staff
      await supabase.from('notifications').insert([
        {
          shop_id: 1,
          title: `New Online Order #${savedOrderId}`,
          description: `${fullName.trim()} booked ${selectedService.name} (₱${grandTotal.toFixed(2)}) via Customer Web.`,
          unread: true,
        },
      ]);

      const confirmedOrder: CustomerOrder = {
        id: savedOrderId,
        order_number: orderNumber,
        customer_name: fullCustomerName,
        category: selectedService.name,
        total_amount: grandTotal,
        status: 'Pending',
        payment_status: 'Unpaid',
        payment_method: paymentMethod,
        notes: notesPayload,
        created_at: insertedOrder?.created_at || new Date().toISOString(),
      };

      setSubmittedOrder(confirmedOrder);
      // Auto-set tracking phone so they can view in tracking tab
      setTrackPhone(cleanPhone);
    } catch (err: any) {
      console.error('Order creation error:', err);
      setOrderError(`Something went wrong: ${err.message || 'Unknown error'}. Please try again.`);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle Tracking by Phone
  const handleFetchOrdersByPhone = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const cleanPhone = (trackPhone || phone).trim().replace(/[^0-9]/g, '');
    if (!cleanPhone) return;

    setTrackingLoading(true);
    setTrackingSearched(true);

    try {
      const { data, error } = await supabase
        .from('orders')
        .select('*')
        .or(`customer_name.ilike.%${cleanPhone.slice(-10)}%,notes.ilike.%${cleanPhone.slice(-10)}%`)
        .order('created_at', { ascending: false });

      if (!error && data) {
        setCustomerOrders(data as CustomerOrder[]);
      } else {
        setCustomerOrders([]);
      }
    } catch (err) {
      console.error('Tracking fetch error:', err);
      setCustomerOrders([]);
    } finally {
      setTrackingLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Completed':
        return 'bg-emerald-100 text-emerald-800 border-emerald-300';
      case 'Ready for Pickup':
        return 'bg-blue-100 text-blue-800 border-blue-300';
      case 'In Progress':
      case 'in Progress':
      case 'Processing':
        return 'bg-[#52c5be] text-gray-950 border-[#3bb7b0] font-bold';
      case 'Cancelled':
      case 'Canceled':
        return 'bg-rose-100 text-rose-800 border-rose-300';
      default:
        return 'bg-amber-100 text-amber-900 border-amber-300';
    }
  };

  const getStatusStepIndex = (status: string) => {
    switch (status) {
      case 'Pending':
        return 0;
      case 'Processing':
      case 'In Progress':
      case 'in Progress':
        return 1;
      case 'Ready for Pickup':
        return 2;
      case 'Completed':
        return 3;
      default:
        return 0;
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-[#eaf6f4] via-[#f4faf9] to-white text-gray-900 flex flex-col font-sans">
      {/* TOP HEADER */}
      <header className="sticky top-0 z-40 bg-white/85 backdrop-blur-md border-b border-[#b7ded8] shadow-xs">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
          {/* Brand */}
          <Link href="/customer" className="flex items-center gap-3 group">
            <div className="w-10 h-10 rounded-2xl bg-gray-950 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform">
              <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                <rect x="3" y="3" width="18" height="18" rx="3" />
                <circle cx="12" cy="13" r="5" />
                <path d="M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
              </svg>
            </div>
            <div>
              <span className="text-xl font-extrabold text-gray-950 tracking-tight block leading-none">
                CleanOps
              </span>
              <span className="text-[11px] font-semibold text-[#2aa09a] tracking-wider uppercase">
                Customer Laundry Hub
              </span>
            </div>
          </Link>

          {/* Navigation Tabs */}
          <nav className="hidden md:flex items-center gap-1 bg-[#cfe8e4]/60 p-1.5 rounded-2xl border border-[#b7ded8]">
            <button
              onClick={() => setActiveTab('order')}
              className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeTab === 'order'
                  ? 'bg-white text-gray-950 shadow-xs'
                  : 'text-gray-700 hover:text-gray-950'
              }`}
            >
              🧺 Book Laundry
            </button>
            <button
              onClick={() => {
                setActiveTab('track');
                if (phone && !trackPhone) {
                  setTrackPhone(phone);
                  handleFetchOrdersByPhone();
                }
              }}
              className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeTab === 'track'
                  ? 'bg-white text-gray-950 shadow-xs'
                  : 'text-gray-700 hover:text-gray-950'
              }`}
            >
              📱 Track by Phone
            </button>
            <button
              onClick={() => setActiveTab('services')}
              className={`px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                activeTab === 'services'
                  ? 'bg-white text-gray-950 shadow-xs'
                  : 'text-gray-700 hover:text-gray-950'
              }`}
            >
              ✨ Rates & Services
            </button>
          </nav>

          {/* Staff Login Link */}
          <div className="flex items-center gap-2 sm:gap-3">
            <button
              onClick={() => {
                setActiveTab('track');
                if (phone && !trackPhone) setTrackPhone(phone);
              }}
              className="md:hidden p-2 rounded-xl bg-[#cfe8e4] text-gray-900 text-xs font-bold"
            >
              Track
            </button>
            <Link
              href="/"
              className="text-xs font-bold px-3.5 py-2 rounded-xl text-gray-700 hover:text-gray-950 bg-gray-100 hover:bg-gray-200 transition-colors"
            >
              Staff Portal
            </Link>
          </div>
        </div>
      </header>

      {/* HERO SECTION */}
      <section className="relative overflow-hidden pt-8 pb-12 sm:pt-12 sm:pb-16 px-4 sm:px-6">
        <div className="max-w-4xl mx-auto text-center space-y-4">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#cfe8e4] border border-[#b7ded8] text-xs font-bold text-[#1f7e79]">
            <Sparkles className="w-3.5 h-3.5" />
            <span>Fast, Fresh & Doorstep Ready</span>
          </div>

          <h1 className="text-3xl sm:text-5xl font-black text-gray-950 tracking-tight leading-tight">
            Book Laundry Instantly with Your Phone Number
          </h1>
          <p className="text-gray-600 text-sm sm:text-base max-w-2xl mx-auto font-medium">
            No complicated passwords or accounts. Simply enter your mobile number to schedule a load,
            track cleaning stages, and inspect receipts in real time.
          </p>

          {/* Quick Nav Switches on Mobile */}
          <div className="flex justify-center gap-2 pt-2 md:hidden">
            <button
              onClick={() => setActiveTab('order')}
              className={`px-4 py-2 rounded-xl text-xs font-bold ${
                activeTab === 'order' ? 'bg-[#52c5be] text-gray-950 shadow-sm' : 'bg-white text-gray-700'
              }`}
            >
              Book Load
            </button>
            <button
              onClick={() => setActiveTab('track')}
              className={`px-4 py-2 rounded-xl text-xs font-bold ${
                activeTab === 'track' ? 'bg-[#52c5be] text-gray-950 shadow-sm' : 'bg-white text-gray-700'
              }`}
            >
              Track Orders
            </button>
            <button
              onClick={() => setActiveTab('services')}
              className={`px-4 py-2 rounded-xl text-xs font-bold ${
                activeTab === 'services' ? 'bg-[#52c5be] text-gray-950 shadow-sm' : 'bg-white text-gray-700'
              }`}
            >
              Services
            </button>
          </div>
        </div>
      </section>

      {/* MAIN CONTENT AREA */}
      <main className="max-w-6xl mx-auto px-4 sm:px-6 pb-20 flex-1 w-full">
        {/* TAB 1: ORDER FORM */}
        {activeTab === 'order' && (
          <div className="max-w-3xl mx-auto">
            {submittedOrder ? (
              /* ORDER SUCCESS CARD */
              <div className="bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-[#b7ded8] text-center space-y-6 animate-in fade-in zoom-in-95">
                <div className="w-16 h-16 rounded-3xl bg-[#52c5be]/20 text-[#2aa09a] mx-auto flex items-center justify-center">
                  <CheckCircle2 className="w-10 h-10" />
                </div>

                <div className="space-y-1">
                  <span className="text-xs font-extrabold uppercase tracking-widest text-[#2aa09a]">
                    Order Placed Successfully
                  </span>
                  <h2 className="text-2xl sm:text-3xl font-black text-gray-950">
                    We&apos;ve Received Your Load!
                  </h2>
                  <p className="text-xs sm:text-sm text-gray-500">
                    Order Reference:{' '}
                    <span className="font-extrabold text-gray-950 bg-gray-100 px-2 py-0.5 rounded-md">
                      #{submittedOrder.id}
                    </span>
                  </p>
                </div>

                <div className="bg-[#cfe8e4]/60 rounded-2xl p-5 text-left text-xs sm:text-sm space-y-2 border border-[#b7ded8]">
                  <div className="flex justify-between font-medium text-gray-700">
                    <span>Customer Identity:</span>
                    <span className="font-bold text-gray-950">{phone}</span>
                  </div>
                  <div className="flex justify-between font-medium text-gray-700">
                    <span>Selected Service:</span>
                    <span className="font-bold text-gray-950">{submittedOrder.category}</span>
                  </div>
                  <div className="flex justify-between font-medium text-gray-700">
                    <span>Delivery Mode:</span>
                    <span className="font-bold text-gray-950">
                      {deliveryType === 'pickup' ? 'Doorstep Pickup & Delivery' : 'Store Drop-off'}
                    </span>
                  </div>
                  <div className="flex justify-between font-medium text-gray-700">
                    <span>Estimated Total:</span>
                    <span className="font-extrabold text-[#1f7e79] text-base">
                      ₱{Number(submittedOrder.total_amount || 0).toFixed(2)}
                    </span>
                  </div>
                </div>

                <div className="flex flex-col sm:flex-row gap-3 pt-2">
                  <button
                    onClick={() => {
                      setActiveTab('track');
                      setTrackPhone(phone);
                      handleFetchOrdersByPhone();
                    }}
                    className="flex-1 py-3.5 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-extrabold rounded-xl text-sm transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <Search className="w-4 h-4" />
                    <span>Track Live Progress by Phone</span>
                  </button>
                  <button
                    onClick={() => {
                      setSubmittedOrder(null);
                      setSpecialInstructions('');
                    }}
                    className="flex-1 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded-xl text-sm transition-colors cursor-pointer"
                  >
                    Place Another Order
                  </button>
                </div>
              </div>
            ) : (
              /* ACTUAL ORDER FORM */
              <form
                onSubmit={handlePlaceOrder}
                className="bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-gray-100 space-y-8"
              >
                {/* SECTION 1: CUSTOMER IDENTITY */}
                <div className="space-y-4">
                  <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="w-8 h-8 rounded-xl bg-[#52c5be]/20 text-[#2aa09a] flex items-center justify-center font-extrabold text-sm">
                        1
                      </div>
                      <h3 className="text-lg font-extrabold text-gray-950">Customer Identity</h3>
                    </div>
                    {isReturningCustomer && (
                      <span className="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 flex items-center gap-1">
                        <Check className="w-3 h-3 stroke-[3]" />
                        <span>Returning Customer</span>
                      </span>
                    )}
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                      Mobile Phone Number <span className="text-rose-500">*</span>
                    </label>
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <Phone className="w-4 h-4" />
                      </div>
                      <input
                        type="tel"
                        required
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        placeholder="0917-123-4567"
                        className="w-full pl-10 pr-10 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                      />
                      {isSearchingPhone && (
                        <div className="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                          <Loader2 className="w-4 h-4 text-[#52c5be] animate-spin" />
                        </div>
                      )}
                    </div>
                    <p className="text-[11px] text-gray-500 mt-1">
                      We use your phone number to identify you and send SMS/status updates.
                    </p>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1.5">
                        Full Name <span className="text-rose-500">*</span>
                      </label>
                      <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                          <User className="w-4 h-4" />
                        </div>
                        <input
                          type="text"
                          required
                          value={fullName}
                          onChange={(e) => setFullName(e.target.value)}
                          placeholder="Your complete name"
                          className="w-full pl-10 pr-3.5 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1.5">
                        Fulfillment Option
                      </label>
                      <div className="grid grid-cols-2 gap-2">
                        <button
                          type="button"
                          onClick={() => setDeliveryType('dropoff')}
                          className={`py-3 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
                            deliveryType === 'dropoff'
                              ? 'bg-[#52c5be] text-gray-950 border-[#2aa09a]'
                              : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                          }`}
                        >
                          <Package className="w-3.5 h-3.5" />
                          <span>Shop Drop-off</span>
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeliveryType('pickup')}
                          className={`py-3 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer flex items-center justify-center gap-1.5 ${
                            deliveryType === 'pickup'
                              ? 'bg-[#52c5be] text-gray-950 border-[#2aa09a]'
                              : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                          }`}
                        >
                          <Truck className="w-3.5 h-3.5" />
                          <span>Pickup (+₱50)</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  {deliveryType === 'pickup' && (
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1.5">
                        Pickup / Delivery Address <span className="text-rose-500">*</span>
                      </label>
                      <div className="relative">
                        <div className="absolute top-3 left-3.5 text-gray-400">
                          <MapPin className="w-4 h-4" />
                        </div>
                        <textarea
                          required={deliveryType === 'pickup'}
                          rows={2}
                          value={address}
                          onChange={(e) => setAddress(e.target.value)}
                          placeholder="House/Unit #, Street, Barangay, City, Landmark"
                          className="w-full pl-10 pr-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                        />
                      </div>
                    </div>
                  )}
                </div>

                {/* SECTION 2: SERVICE SELECTION */}
                <div className="space-y-4">
                  <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div className="flex items-center gap-2.5">
                      <div className="w-8 h-8 rounded-xl bg-[#52c5be]/20 text-[#2aa09a] flex items-center justify-center font-extrabold text-sm">
                        2
                      </div>
                      <h3 className="text-lg font-extrabold text-gray-950">Select Laundry Service</h3>
                    </div>
                    <span className="text-xs text-gray-500 font-semibold">
                      {selectedService.turnaround} Turnaround
                    </span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {SERVICES.map((s) => {
                      const isSelected = selectedService.id === s.id;
                      return (
                        <div
                          key={s.id}
                          onClick={() => setSelectedService(s)}
                          className={`p-4 rounded-2xl border transition-all cursor-pointer flex items-start gap-3.5 ${
                            isSelected
                              ? 'bg-[#cfe8e4]/70 border-[#2aa09a] ring-2 ring-[#52c5be]/40'
                              : 'bg-white border-gray-200 hover:border-gray-300 hover:bg-gray-50/50'
                          }`}
                        >
                          <div className="text-2xl shrink-0 mt-0.5">{s.icon}</div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between">
                              <h4 className="font-extrabold text-sm text-gray-950">{s.name}</h4>
                              <span className="text-xs font-extrabold text-[#1f7e79]">
                                ₱{s.rate.toFixed(2)}/{s.unit}
                              </span>
                            </div>
                            <p className="text-[11px] text-gray-500 mt-0.5 line-clamp-2">{s.desc}</p>
                          </div>
                        </div>
                      );
                    })}
                  </div>

                  {/* Quantity / Weight Stepper */}
                  <div className="bg-gray-50 rounded-2xl p-4 border border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                      <span className="block text-xs font-bold text-gray-800">
                        Estimated Load ({selectedService.unit})
                      </span>
                      <span className="text-[11px] text-gray-500">
                        Shop attendants will verify the exact scale weight upon receipt.
                      </span>
                    </div>

                    <div className="flex items-center gap-3">
                      <button
                        type="button"
                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                        className="w-9 h-9 rounded-xl bg-white border border-gray-300 font-extrabold text-gray-800 hover:bg-gray-100 flex items-center justify-center cursor-pointer"
                      >
                        -
                      </button>
                      <span className="text-lg font-black text-gray-950 min-w-[50px] text-center">
                        {quantity} {selectedService.unit}
                      </span>
                      <button
                        type="button"
                        onClick={() => setQuantity(quantity + 1)}
                        className="w-9 h-9 rounded-xl bg-white border border-gray-300 font-extrabold text-gray-800 hover:bg-gray-100 flex items-center justify-center cursor-pointer"
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>

                {/* SECTION 3: SCHEDULE & PAYMENT */}
                <div className="space-y-4">
                  <div className="flex items-center gap-2.5 border-b border-gray-100 pb-3">
                    <div className="w-8 h-8 rounded-xl bg-[#52c5be]/20 text-[#2aa09a] flex items-center justify-center font-extrabold text-sm">
                      3
                    </div>
                    <h3 className="text-lg font-extrabold text-gray-950">Schedule & Payment</h3>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1.5">
                        Preferred Date
                      </label>
                      <input
                        type="date"
                        value={pickupDate}
                        onChange={(e) => setPickupDate(e.target.value)}
                        className="w-full px-3.5 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                      />
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-gray-700 mb-1.5">
                        Time Preference
                      </label>
                      <select
                        value={pickupTimeSlot}
                        onChange={(e) => setPickupTimeSlot(e.target.value)}
                        className="w-full px-3.5 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                      >
                        <option value="Morning (8:00 AM - 12:00 PM)">Morning (8:00 AM - 12:00 PM)</option>
                        <option value="Afternoon (1:00 PM - 5:00 PM)">Afternoon (1:00 PM - 5:00 PM)</option>
                        <option value="Evening (5:00 PM - 8:00 PM)">Evening (5:00 PM - 8:00 PM)</option>
                      </select>
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                      Payment Preference
                    </label>
                    <div className="grid grid-cols-3 gap-2">
                      {(['Cash', 'GCash', 'Maya'] as const).map((method) => (
                        <button
                          key={method}
                          type="button"
                          onClick={() => setPaymentMethod(method)}
                          className={`py-2.5 px-3 rounded-xl text-xs font-bold border transition-all cursor-pointer text-center ${
                            paymentMethod === method
                              ? 'bg-gray-950 text-white border-gray-950 shadow-sm'
                              : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'
                          }`}
                        >
                          {method === 'Cash' ? '💵 Cash on Hand' : method === 'GCash' ? '💙 GCash' : '💚 Maya'}
                        </button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-1.5">
                      Special Garment Instructions (Optional)
                    </label>
                    <input
                      type="text"
                      value={specialInstructions}
                      onChange={(e) => setSpecialInstructions(e.target.value)}
                      placeholder="e.g., Separate colored shirts, extra softener, delicate fabrics"
                      className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                    />
                  </div>
                </div>

                {/* ERROR BANNER */}
                {orderError && (
                  <div className="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                    <span>{orderError}</span>
                  </div>
                )}

                {/* ORDER ESTIMATION SUMMARY BOX */}
                <div className="p-5 rounded-2xl bg-[#cfe8e4]/60 border border-[#b7ded8] space-y-2.5">
                  <div className="flex justify-between text-xs font-medium text-gray-700">
                    <span>
                      {selectedService.name} ({quantity} {selectedService.unit} @ ₱{selectedService.rate.toFixed(2)})
                    </span>
                    <span>₱{serviceSubtotal.toFixed(2)}</span>
                  </div>

                  {deliveryFee > 0 && (
                    <div className="flex justify-between text-xs font-medium text-gray-700">
                      <span>Doorstep Pickup & Delivery</span>
                      <span>₱{deliveryFee.toFixed(2)}</span>
                    </div>
                  )}

                  <div className="pt-2 border-t border-[#b7ded8] flex justify-between items-baseline">
                    <div>
                      <span className="text-xs font-bold text-gray-600 block">Total Payable</span>
                      <span className="text-[11px] text-gray-500">Pay upon drop-off or delivery</span>
                    </div>
                    <span className="text-2xl font-black text-gray-950">₱{grandTotal.toFixed(2)}</span>
                  </div>
                </div>

                {/* SUBMIT BUTTON */}
                {orderError && (
                  <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                    <AlertCircle className="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
                    <div>
                      <p className="text-sm font-bold text-rose-800">Order Failed</p>
                      <p className="text-xs text-rose-600 mt-0.5">{orderError}</p>
                    </div>
                  </div>
                )}

                <button
                  type="submit"
                  disabled={submitting}
                  className="w-full py-4 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-black rounded-2xl text-base shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                >
                  {submitting ? (
                    <>
                      <Loader2 className="w-5 h-5 animate-spin" />
                      <span>Submitting Order...</span>
                    </>
                  ) : (
                    <>
                      <span>Confirm & Book Laundry</span>
                      <ArrowRight className="w-5 h-5" />
                    </>
                  )}
                </button>
              </form>
            )}
          </div>
        )}

        {/* TAB 2: TRACK BY PHONE NUMBER */}
        {activeTab === 'track' && (
          <div className="max-w-3xl mx-auto space-y-6">
            <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-[#52c5be]/20 text-[#2aa09a] flex items-center justify-center font-bold">
                  <Phone className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-xl font-extrabold text-gray-950">Look Up Orders by Phone</h3>
                  <p className="text-xs text-gray-500">
                    Enter the phone number you used when placing your laundry loads.
                  </p>
                </div>
              </div>

              <form onSubmit={handleFetchOrdersByPhone} className="flex gap-2">
                <div className="relative flex-1">
                  <input
                    type="tel"
                    required
                    value={trackPhone}
                    onChange={(e) => setTrackPhone(e.target.value)}
                    placeholder="Enter phone number (e.g. 0917-123-4567)"
                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>
                <button
                  type="submit"
                  disabled={trackingLoading}
                  className="px-6 py-3 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-sm transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-75"
                >
                  {trackingLoading ? (
                    <Loader2 className="w-4 h-4 animate-spin" />
                  ) : (
                    <Search className="w-4 h-4 stroke-[2.5]" />
                  )}
                  <span>Search</span>
                </button>
              </form>
            </div>

            {/* RESULTS LIST */}
            {trackingSearched && (
              <div className="space-y-4">
                {customerOrders.length === 0 ? (
                  <div className="bg-white rounded-3xl p-10 text-center space-y-3 shadow-md border border-gray-100">
                    <Package className="w-12 h-12 text-gray-300 mx-auto" />
                    <h4 className="text-lg font-bold text-gray-950">No Orders Found</h4>
                    <p className="text-xs text-gray-500 max-w-sm mx-auto">
                      We couldn&apos;t find any past or ongoing orders linked to &quot;{trackPhone}&quot;.
                      Check the number or place your first order today!
                    </p>
                    <button
                      onClick={() => setActiveTab('order')}
                      className="mt-2 px-5 py-2.5 bg-[#52c5be] text-gray-950 font-bold rounded-xl text-xs shadow-sm hover:bg-[#47b5ae] transition-colors cursor-pointer"
                    >
                      Book a Load Now
                    </button>
                  </div>
                ) : (
                  customerOrders.map((order) => {
                    const stepIdx = getStatusStepIndex(order.status);
                    return (
                      <div
                        key={order.id}
                        className="bg-white rounded-3xl p-6 shadow-md border border-gray-100 space-y-5"
                      >
                        {/* Header Row */}
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="text-base font-extrabold text-gray-950">
                                Order #{order.id}
                              </span>
                              <span
                                className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${getStatusColor(
                                  order.status
                                )}`}
                              >
                                {order.status}
                              </span>
                            </div>
                            <span className="text-xs text-gray-500 mt-0.5 block">
                              {order.category || 'Wash & Fold'} •{' '}
                              {order.created_at
                                ? new Date(order.created_at).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                  })
                                : 'Recent'}
                            </span>
                          </div>

                          <div className="text-left sm:text-right">
                            <span className="text-xl font-extrabold text-gray-950 block">
                              ₱{Number(order.total_amount || 0).toFixed(2)}
                            </span>
                            <span
                              className={`text-[11px] font-semibold ${
                                order.payment_status === 'Paid' ? 'text-emerald-700' : 'text-amber-700'
                              }`}
                            >
                              Payment: {order.payment_status || 'Unpaid'}
                            </span>
                          </div>
                        </div>

                        {/* Interactive Status Timeline */}
                        <div className="py-2">
                          <span className="text-[11px] font-extrabold text-gray-500 uppercase tracking-wider block mb-3">
                            Live Cleaning Progress
                          </span>
                          <div className="grid grid-cols-4 gap-2 text-center">
                            {[
                              { label: 'Received', icon: Receipt },
                              { label: 'Washing / Drying', icon: Droplets },
                              { label: 'Quality Fold', icon: Wind },
                              { label: 'Ready', icon: CheckCircle2 },
                            ].map((step, idx) => {
                              const isCompleted = idx <= stepIdx;
                              const isCurrent = idx === stepIdx;
                              return (
                                <div key={step.label} className="space-y-1.5">
                                  <div
                                    className={`w-9 h-9 rounded-xl mx-auto flex items-center justify-center transition-all ${
                                      isCurrent
                                        ? 'bg-[#52c5be] text-gray-950 ring-4 ring-[#52c5be]/20 font-bold scale-105'
                                        : isCompleted
                                        ? 'bg-[#cfe8e4] text-[#1f7e79]'
                                        : 'bg-gray-100 text-gray-400'
                                    }`}
                                  >
                                    <step.icon className="w-4 h-4" />
                                  </div>
                                  <span
                                    className={`text-[11px] block font-semibold leading-tight ${
                                      isCompleted ? 'text-gray-950' : 'text-gray-400'
                                    }`}
                                  >
                                    {step.label}
                                  </span>
                                </div>
                              );
                            })}
                          </div>
                        </div>

                        {/* Notes / Details */}
                        {order.notes && (
                          <div className="bg-gray-50 rounded-xl p-3 text-xs text-gray-600 font-medium">
                            <span className="font-bold text-gray-700 block mb-0.5">Order Details:</span>
                            {order.notes}
                          </div>
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            )}
          </div>
        )}

        {/* TAB 3: SERVICES SHOWCASE */}
        {activeTab === 'services' && (
          <div className="max-w-4xl mx-auto space-y-8">
            <div className="text-center space-y-2">
              <h2 className="text-2xl sm:text-3xl font-extrabold text-gray-950">
                Transparent Pricing & Turnaround Times
              </h2>
              <p className="text-gray-600 text-sm">
                No hidden costs. Every load is treated with premium hypoallergenic detergents.
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
              {SERVICES.map((s) => (
                <div
                  key={s.id}
                  className="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between space-y-4 hover:shadow-lg transition-shadow"
                >
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <span className="text-3xl">{s.icon}</span>
                      <span className="text-xs font-bold px-2.5 py-1 rounded-full bg-[#cfe8e4] text-[#1f7e79]">
                        {s.turnaround}
                      </span>
                    </div>

                    <div>
                      <h3 className="text-lg font-bold text-gray-950">{s.name}</h3>
                      <span className="text-xs text-gray-500 font-medium">{s.category}</span>
                    </div>

                    <p className="text-xs text-gray-600 leading-relaxed">{s.desc}</p>
                  </div>

                  <div className="pt-3 border-t border-gray-100 flex items-center justify-between">
                    <div>
                      <span className="text-xl font-black text-gray-950">₱{s.rate.toFixed(2)}</span>
                      <span className="text-xs text-gray-500"> / {s.unit}</span>
                    </div>
                    <button
                      onClick={() => {
                        setSelectedService(s);
                        setActiveTab('order');
                      }}
                      className="px-3.5 py-1.5 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-xs transition-colors cursor-pointer"
                    >
                      Choose
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Quality Guarantee Box */}
            <div className="bg-[#cfe8e4] rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 border border-[#b7ded8]">
              <div className="space-y-1 text-center sm:text-left">
                <div className="flex items-center justify-center sm:justify-start gap-2 text-[#1f7e79] font-extrabold text-sm">
                  <ShieldCheck className="w-5 h-5" />
                  <span>The CleanOps Hygiene Promise</span>
                </div>
                <h4 className="text-xl font-bold text-gray-950">100% Satisfaction or Free Rewash</h4>
                <p className="text-xs text-gray-700 max-w-md">
                  Separate washer cycles for each customer, gentle temperature control, and complete
                  garment safety guarantee.
                </p>
              </div>
              <button
                onClick={() => setActiveTab('order')}
                className="px-6 py-3.5 bg-gray-950 hover:bg-gray-800 text-white font-bold rounded-2xl text-sm transition-all shadow-md shrink-0 cursor-pointer"
              >
                Schedule First Load
              </button>
            </div>
          </div>
        )}
      </main>

      {/* FOOTER */}
      <footer className="bg-white border-t border-gray-200 py-8 px-4 sm:px-6 text-center text-xs text-gray-500 space-y-2">
        <p className="font-semibold text-gray-700">CleanOps Laundry Services • Powered by CleanOps Cloud Platform</p>
        <p>Open Monday – Sunday, 8:00 AM – 8:00 PM • Fast Turnaround Guaranteed</p>
        <div className="pt-2 flex justify-center gap-4 text-[#2aa09a] font-bold">
          <button onClick={() => setActiveTab('order')} className="hover:underline cursor-pointer">
            Book Laundry
          </button>
          <span>•</span>
          <button onClick={() => setActiveTab('track')} className="hover:underline cursor-pointer">
            Track by Phone
          </button>
          <span>•</span>
          <Link href="/" className="hover:underline">
            Admin & Staff Login
          </Link>
        </div>
      </footer>
    </div>
  );
}
