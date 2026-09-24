'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useAuth } from '@/components/AuthProvider';
import { supabase, OrderItem, ShopInfo } from '@/lib/supabaseClient';
import {
  LayoutDashboard,
  Receipt,
  Users,
  Sparkles,
  CalendarClock,
  FileBarChart,
  Bell,
  Settings,
  Search,
  MessageSquare,
  User,
  Clock,
  CheckCircle2,
  FileX,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  LogOut,
  Loader2,
  Menu,
  X,
  Plus,
  PlusCircle,
  Filter,
  Eye,
  Check,
  DollarSign,
  TrendingUp,
  Store,
  Phone,
  MapPin,
  Save,
  Tag,
  UserCheck,
  Trash2,
  Mail,
  Briefcase,
  Package,
  AlertTriangle,
  Minus,
} from 'lucide-react';

type AdminTab =
  | 'Dashboard'
  | 'Orders'
  | 'Customers'
  | 'Staff'
  | 'Inventory'
  | 'Services'
  | 'Schedule'
  | 'Reports'
  | 'Notifications'
  | 'Settings';

export default function AdminDashboardPage() {
  const { user, profile, loading: authLoading, signOut } = useAuth();
  const [activeTab, setActiveTab] = useState<AdminTab>('Dashboard');
  const [shop, setShop] = useState<ShopInfo | null>(null);
  const [orders, setOrders] = useState<OrderItem[]>([]);
  const [timeframe, setTimeframe] = useState<'Last 7 days' | 'Last 30 days' | 'This Month'>('Last 7 days');
  const [showTimeframeDropdown, setShowTimeframeDropdown] = useState(false);
  const [loading, setLoading] = useState(true);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Modals & Popups
  const [selectedOrder, setSelectedOrder] = useState<OrderItem | null>(null);
  const [showCreateOrderModal, setShowCreateOrderModal] = useState(false);
  const [showNotificationsDropdown, setShowNotificationsDropdown] = useState(false);
  const [statusFilter, setStatusFilter] = useState<'All' | 'Processing' | 'in Progress' | 'In Progress' | 'Completed' | 'Cancelled'>('All');
  const [currentOrdersPage, setCurrentOrdersPage] = useState(1);

  // New Order Form State
  const [newCustomerName, setNewCustomerName] = useState('');
  const [newCategory, setNewCategory] = useState('Wash & Fold');
  const [newAmount, setNewAmount] = useState('350');
  const [newPayment, setNewPayment] = useState<'Paid' | 'Unpaid'>('Paid');
  const [newStatus, setNewStatus] = useState<'Pending' | 'In Progress' | 'Completed'>('In Progress');

  // Shop Settings Form State
  const [shopName, setShopName] = useState('CleanOps Laundry Shop');
  const [shopAddress, setShopAddress] = useState('123 Rizal Ave, Metro Manila');
  const [shopContact, setShopContact] = useState('0917 123 4567');
  const [settingsSaved, setSettingsSaved] = useState(false);

  // Staff Management State
  interface StaffMember {
    id: string;
    name: string;
    email: string;
    phone: string;
    role: 'Supervisor' | 'Laundry Attendant' | 'Washer' | 'Dryer Specialist';
    schedule: string[];
    status: 'Active' | 'On Leave';
    joinedDate: string;
  }

  const [staffList, setStaffList] = useState<StaffMember[]>([
    {
      id: 'st-1',
      name: 'Maria Santos',
      email: 'maria.santos@cleanops.com',
      phone: '0917-889-1234',
      role: 'Supervisor',
      schedule: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
      status: 'Active',
      joinedDate: 'Jan 15, 2025',
    },
    {
      id: 'st-2',
      name: 'Carlos Dizon',
      email: 'carlos.dizon@cleanops.com',
      phone: '0928-554-9821',
      role: 'Washer',
      schedule: ['Mon', 'Wed', 'Fri', 'Sat'],
      status: 'Active',
      joinedDate: 'Feb 10, 2025',
    },
    {
      id: 'st-3',
      name: 'Jennylyn Flores',
      email: 'jenny.flores@cleanops.com',
      phone: '0939-441-8765',
      role: 'Laundry Attendant',
      schedule: ['Tue', 'Thu', 'Sat', 'Sun'],
      status: 'Active',
      joinedDate: 'Mar 01, 2025',
    },
    {
      id: 'st-4',
      name: 'Ricardo Gomez',
      email: 'ricardo.g@cleanops.com',
      phone: '0915-332-1109',
      role: 'Dryer Specialist',
      schedule: ['Mon', 'Tue', 'Thu', 'Fri', 'Sun'],
      status: 'On Leave',
      joinedDate: 'Dec 05, 2024',
    },
  ]);

  const [showAddStaffModal, setShowAddStaffModal] = useState(false);
  const [newStaffName, setNewStaffName] = useState('');
  const [newStaffEmail, setNewStaffEmail] = useState('');
  const [newStaffPhone, setNewStaffPhone] = useState('');
  const [newStaffRole, setNewStaffRole] = useState<'Supervisor' | 'Laundry Attendant' | 'Washer' | 'Dryer Specialist'>('Laundry Attendant');
  const [newStaffDays, setNewStaffDays] = useState<string[]>(['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
  const [staffRoleFilter, setStaffRoleFilter] = useState<string>('All');

  // Inventory / Stock State
  interface InventoryItem {
    id: string;
    name: string;
    quantity: number;
    unit: string;
    minStock: number;
    category: string;
  }

  const [inventoryList, setInventoryList] = useState<InventoryItem[]>([
    { id: 'inv-1', name: 'Ariel Professional Powder', quantity: 48, unit: 'kg', minStock: 15, category: 'Detergent' },
    { id: 'inv-2', name: 'Downy Sunrise Fresh Softener', quantity: 24, unit: 'liters', minStock: 10, category: 'Softener' },
    { id: 'inv-3', name: 'Zonrox Color-Safe Bleach', quantity: 8, unit: 'liters', minStock: 12, category: 'Bleach' },
    { id: 'inv-4', name: 'Biodegradable Laundry Bags (L)', quantity: 220, unit: 'pcs', minStock: 50, category: 'Packaging' },
    { id: 'inv-5', name: 'Bounce Scented Dryer Sheets', quantity: 65, unit: 'pcs', minStock: 20, category: 'Supplies' },
    { id: 'inv-6', name: 'Vanish Power O2 Stain Remover', quantity: 5, unit: 'kg', minStock: 10, category: 'Chemicals' },
  ]);

  const [showAddInventoryModal, setShowAddInventoryModal] = useState(false);
  const [newInvName, setNewInvName] = useState('');
  const [newInvQuantity, setNewInvQuantity] = useState('20');
  const [newInvUnit, setNewInvUnit] = useState('kg');
  const [newInvMinStock, setNewInvMinStock] = useState('10');
  const [newInvCategory, setNewInvCategory] = useState('Detergent');

  // Fallback demo metrics if Supabase tables haven't been seeded yet
  const [metrics, setMetrics] = useState({
    total: 28,
    inProgress: 12,
    completed: 14,
    canceled: 2,
  });

  const sampleNotifications = [
    { id: 1, title: 'Order #1042 ready', desc: 'Maria Santos load has finished drying', time: '5m ago', unread: true },
    { id: 2, title: 'New Booking from Web', desc: 'Sarah Connor requested Express Wash', time: '25m ago', unread: true },
    { id: 3, title: 'Payment Confirmed', desc: 'Juan Dela Cruz settled ₱720.00', time: '1h ago', unread: false },
    { id: 4, title: 'Machine 2 Maintenance', desc: 'Routine filter cleanse scheduled tonight', time: '3h ago', unread: false },
  ];

  const servicesCatalog = [
    { name: 'Wash & Fold', price: '₱35.00 / kg', turnAround: '24 Hours', active: true },
    { name: 'Dry Cleaning', price: '₱120.00 / pc', turnAround: '48 Hours', active: true },
    { name: 'Express Wash', price: '₱55.00 / kg', turnAround: '4 Hours', active: true },
    { name: 'Beddings & Comforter', price: '₱80.00 / pc', turnAround: '24 Hours', active: true },
    { name: 'Steam Press / Ironing', price: '₱25.00 / pc', turnAround: '12 Hours', active: true },
    { name: 'Delicates & Silks', price: '₱150.00 / pc', turnAround: '48 Hours', active: false },
  ];

  const machinesSchedule = [
    { id: 'Washer 01 (10kg)', status: 'Washing', customer: 'Maria Santos (#1042)', remaining: '18 mins' },
    { id: 'Washer 02 (15kg)', status: 'Available', customer: 'Idle - Ready for load', remaining: 'Ready' },
    { id: 'Washer 03 (10kg)', status: 'Rinsing', customer: 'David Miller (#1039)', remaining: '8 mins' },
    { id: 'Dryer 01 (12kg)', status: 'Drying', customer: 'Sarah Connor (#1040)', remaining: '22 mins' },
    { id: 'Dryer 02 (12kg)', status: 'Available', customer: 'Idle - Cooled down', remaining: 'Ready' },
    { id: 'Dryer 03 (15kg)', status: 'Drying', customer: 'Juan Dela Cruz (#1041)', remaining: '12 mins' },
  ];

  useEffect(() => {
    // Allows direct access & preview without signing in
    if (!authLoading) {
      loadDashboardData();
    }
  }, [user, authLoading]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      if (user) {
        const { data: shopData } = await supabase
          .from('shops')
          .select('id, shop_name, address, contact_number')
          .eq('user_id', user.id)
          .single();

        if (shopData) {
          setShop(shopData);
          setShopName(shopData.shop_name || 'CleanOps Laundry Shop');
          setShopAddress(shopData.address || '123 Rizal Ave, Metro Manila');
          setShopContact(shopData.contact_number || '0917 123 4567');
        } else {
          setShop({
            id: profile?.shop_id || 1,
            shop_name: 'CleanOps Laundry Shop',
          });
        }
      } else {
        // Fallback demo shop for direct access without sign-in
        setShop({
          id: 1,
          shop_name: 'CleanOps Laundry Shop',
        });
      }

      // Fetch Orders from Supabase
      const { data: ordersData, error: ordersErr } = await supabase
        .from('orders')
        .select('*')
        .order('created_at', { ascending: false })
        .limit(20);

      if (!ordersErr && ordersData && ordersData.length > 0) {
        setOrders(ordersData as OrderItem[]);
        updateMetrics(ordersData as OrderItem[]);
      } else {
        // High fidelity mock orders matching the mockup design
        const initialMock: OrderItem[] = [
          // 5 Processing Orders
          {
            id: '1050',
            shop_id: 1,
            customer_name: 'Juan Dela Cruz',
            category: 'Wash & Fold',
            status: 'Processing',
            total_amount: 350.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 12 * 60 * 1000).toISOString(),
          },
          {
            id: '1049',
            shop_id: 1,
            customer_name: 'Sarah Connor',
            category: 'Dry Cleaning',
            status: 'Processing',
            total_amount: 720.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 35 * 60 * 1000).toISOString(),
          },
          {
            id: '1048',
            shop_id: 1,
            customer_name: 'David Miller',
            category: 'Express Wash',
            status: 'Processing',
            total_amount: 550.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 55 * 60 * 1000).toISOString(),
          },
          {
            id: '1047',
            shop_id: 1,
            customer_name: 'Maria Santos',
            category: 'Beddings & Comforter',
            status: 'Processing',
            total_amount: 480.0,
            payment_status: 'Unpaid',
            created_at: new Date(Date.now() - 85 * 60 * 1000).toISOString(),
          },
          {
            id: '1046',
            shop_id: 1,
            customer_name: 'Ricardo Gomez',
            category: 'Steam Press / Ironing',
            status: 'Processing',
            total_amount: 250.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 110 * 60 * 1000).toISOString(),
          },
          // 3 in Progress Orders
          {
            id: '1045',
            shop_id: 1,
            customer_name: 'Carlos Dizon',
            category: 'Wash & Fold',
            status: 'in Progress',
            total_amount: 350.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1044',
            shop_id: 1,
            customer_name: 'Jennylyn Flores',
            category: 'Dry Cleaning',
            status: 'in Progress',
            total_amount: 600.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1043',
            shop_id: 1,
            customer_name: 'Elena Gilbert',
            category: 'Express Wash',
            status: 'in Progress',
            total_amount: 450.0,
            payment_status: 'Unpaid',
            created_at: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
          },
          // 14 Completed Orders
          {
            id: '1042',
            shop_id: 1,
            customer_name: 'Maria Santos',
            category: 'Wash & Fold',
            status: 'Completed',
            total_amount: 350.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1041',
            shop_id: 1,
            customer_name: 'Juan Dela Cruz',
            category: 'Dry Cleaning',
            status: 'Completed',
            total_amount: 720.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 7 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1040',
            shop_id: 1,
            customer_name: 'Sarah Connor',
            category: 'Express Wash',
            status: 'Completed',
            total_amount: 500.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 9 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1039',
            shop_id: 1,
            customer_name: 'David Miller',
            category: 'Beddings & Linen',
            status: 'Completed',
            total_amount: 450.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1037',
            shop_id: 1,
            customer_name: 'Liza Soberano',
            category: 'Delicates & Silks',
            status: 'Completed',
            total_amount: 680.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 16 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1036',
            shop_id: 1,
            customer_name: 'Kathryn Bernardo',
            category: 'Wash & Fold',
            status: 'Completed',
            total_amount: 320.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 20 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1035',
            shop_id: 1,
            customer_name: 'Daniel Padilla',
            category: 'Dry Cleaning',
            status: 'Completed',
            total_amount: 850.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1034',
            shop_id: 1,
            customer_name: 'Nadine Lustre',
            category: 'Express Wash',
            status: 'Completed',
            total_amount: 420.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 28 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1033',
            shop_id: 1,
            customer_name: 'James Reid',
            category: 'Beddings & Comforter',
            status: 'Completed',
            total_amount: 510.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 32 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1032',
            shop_id: 1,
            customer_name: 'Bea Alonzo',
            category: 'Steam Press / Ironing',
            status: 'Completed',
            total_amount: 300.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 36 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1031',
            shop_id: 1,
            customer_name: 'John Lloyd Cruz',
            category: 'Wash & Fold',
            status: 'Completed',
            total_amount: 360.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 40 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1030',
            shop_id: 1,
            customer_name: 'Anne Curtis',
            category: 'Delicates & Silks',
            status: 'Completed',
            total_amount: 590.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 44 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1029',
            shop_id: 1,
            customer_name: 'Vice Ganda',
            category: 'Dry Cleaning',
            status: 'Completed',
            total_amount: 950.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 48 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1028',
            shop_id: 1,
            customer_name: 'Dingdong Dantes',
            category: 'Wash & Fold',
            status: 'Completed',
            total_amount: 380.0,
            payment_status: 'Paid',
            created_at: new Date(Date.now() - 52 * 60 * 60 * 1000).toISOString(),
          },
          // 3 Cancelled Orders
          {
            id: '1038',
            shop_id: 1,
            customer_name: 'Elena Gilbert',
            category: 'Delicates',
            status: 'Cancelled',
            total_amount: 280.0,
            payment_status: 'Unpaid',
            created_at: new Date(Date.now() - 25 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1027',
            shop_id: 1,
            customer_name: 'Marian Rivera',
            category: 'Express Wash',
            status: 'Cancelled',
            total_amount: 400.0,
            payment_status: 'Unpaid',
            created_at: new Date(Date.now() - 30 * 60 * 60 * 1000).toISOString(),
          },
          {
            id: '1026',
            shop_id: 1,
            customer_name: 'Coco Martin',
            category: 'Wash & Fold',
            status: 'Cancelled',
            total_amount: 350.0,
            payment_status: 'Unpaid',
            created_at: new Date(Date.now() - 38 * 60 * 60 * 1000).toISOString(),
          },
        ];
        setOrders(initialMock);
        updateMetrics(initialMock);
      }
    } catch (err) {
      console.error('Error fetching dashboard data:', err);
    } finally {
      setLoading(false);
    }
  };

  const updateMetrics = (list: OrderItem[]) => {
    const total = list.length;
    const inProgress = list.filter(
      (o) =>
        o.status === 'In Progress' ||
        o.status === 'in Progress' ||
        o.status === 'Pending' ||
        o.status === 'Processing'
    ).length;
    const completed = list.filter((o) => o.status === 'Completed').length;
    const canceled = list.filter((o) => o.status === 'Cancelled' || o.status === 'Canceled').length;

    setMetrics({
      total: total || 25,
      inProgress: inProgress || 8,
      completed: completed || 14,
      canceled: canceled || 3,
    });
  };

  const handleUpdateOrderStatus = async (orderId: string | number, newStatusVal: 'In Progress' | 'Completed' | 'Cancelled') => {
    const updated = orders.map((o) => (o.id === orderId ? { ...o, status: newStatusVal } : o));
    setOrders(updated);
    updateMetrics(updated);
    if (selectedOrder && selectedOrder.id === orderId) {
      setSelectedOrder({ ...selectedOrder, status: newStatusVal });
    }

    try {
      await supabase.from('orders').update({ status: newStatusVal }).eq('id', orderId);
    } catch (e) {
      // ignore
    }
  };

  const handleCreateOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCustomerName.trim()) return;

    const newOrderObj: OrderItem = {
      id: String(Math.floor(1000 + Math.random() * 9000)),
      shop_id: shop?.id || 1,
      customer_name: newCustomerName.trim(),
      category: newCategory,
      total_amount: Number(newAmount) || 300,
      payment_status: newPayment,
      status: newStatus,
      created_at: new Date().toISOString(),
    };

    const updated = [newOrderObj, ...orders];
    setOrders(updated);
    updateMetrics(updated);

    try {
      await supabase.from('orders').insert([newOrderObj]);
    } catch (e) {
      // ignore
    }

    setShowCreateOrderModal(false);
    setNewCustomerName('');
    setNewAmount('350');
  };

  const handleSaveSettings = (e: React.FormEvent) => {
    e.preventDefault();
    setSettingsSaved(true);
    setTimeout(() => setSettingsSaved(false), 2500);
  };

  const handleToggleDay = (day: string) => {
    if (newStaffDays.includes(day)) {
      setNewStaffDays(newStaffDays.filter((d) => d !== day));
    } else {
      setNewStaffDays([...newStaffDays, day]);
    }
  };

  const handleAddStaff = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newStaffName.trim()) return;

    const newStaff: StaffMember = {
      id: `st-${Date.now()}`,
      name: newStaffName.trim(),
      email: newStaffEmail.trim() || `${newStaffName.toLowerCase().replace(/\s+/g, '.')}@cleanops.com`,
      phone: newStaffPhone.trim() || '0917-000-0000',
      role: newStaffRole,
      schedule: newStaffDays.length > 0 ? newStaffDays : ['Mon', 'Wed', 'Fri'],
      status: 'Active',
      joinedDate: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
    };

    setStaffList([newStaff, ...staffList]);
    setShowAddStaffModal(false);
    setNewStaffName('');
    setNewStaffEmail('');
    setNewStaffPhone('');
    setNewStaffPassword('');
    setNewStaffDays(['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);
  };

  const handleDeleteStaff = (id: string) => {
    setStaffList(staffList.filter((s) => s.id !== id));
  };

  const handleToggleStaffStatus = (id: string) => {
    setStaffList(
      staffList.map((s) =>
        s.id === id ? { ...s, status: s.status === 'Active' ? 'On Leave' : 'Active' } : s
      )
    );
  };

  const handleUpdateStock = (id: string, delta: number) => {
    setInventoryList(
      inventoryList.map((item) =>
        item.id === id ? { ...item, quantity: Math.max(0, item.quantity + delta) } : item
      )
    );
  };

  const handleAddInventory = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newInvName.trim()) return;

    const newItem: InventoryItem = {
      id: `inv-${Date.now()}`,
      name: newInvName.trim(),
      quantity: Number(newInvQuantity) || 10,
      unit: newInvUnit,
      minStock: Number(newInvMinStock) || 10,
      category: newInvCategory,
    };

    setInventoryList([...inventoryList, newItem]);
    setShowAddInventoryModal(false);
    setNewInvName('');
    setNewInvQuantity('20');
  };

  const navItems: { label: AdminTab; icon: React.ElementType }[] = [
    { label: 'Dashboard', icon: LayoutDashboard },
    { label: 'Orders', icon: Receipt },
    { label: 'Customers', icon: Users },
    { label: 'Staff', icon: UserCheck },
    { label: 'Inventory', icon: Package },
    { label: 'Services', icon: Sparkles },
    { label: 'Schedule', icon: CalendarClock },
    { label: 'Reports', icon: FileBarChart },
    { label: 'Notifications', icon: Bell },
    { label: 'Settings', icon: Settings },
  ];

  // Dynamic chart data based on timeframe
  const chartDays =
    timeframe === 'Last 7 days'
      ? [
          { day: 'Mon', count: 18, height: '65%' },
          { day: 'Tue', count: 24, height: '85%' },
          { day: 'Wed', count: 14, height: '50%' },
          { day: 'Thu', count: 28, height: '100%' },
          { day: 'Fri', count: 22, height: '78%' },
          { day: 'Sat', count: 26, height: '92%' },
          { day: 'Sun', count: 16, height: '58%' },
        ]
      : timeframe === 'Last 30 days'
      ? [
          { day: 'Wk 1', count: 95, height: '70%' },
          { day: 'Wk 2', count: 130, height: '95%' },
          { day: 'Wk 3', count: 110, height: '80%' },
          { day: 'Wk 4', count: 140, height: '100%' },
        ]
      : [
          { day: 'Day 1-7', count: 88, height: '60%' },
          { day: 'Day 8-14', count: 120, height: '85%' },
          { day: 'Day 15-21', count: 145, height: '100%' },
          { day: 'Day 22-30', count: 105, height: '75%' },
        ];

  const currentDate = new Date().toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });

  const filteredOrders = orders.filter((o) => {
    const matchesSearch =
      o.customer_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      String(o.id).includes(searchQuery) ||
      (o.category && o.category.toLowerCase().includes(searchQuery.toLowerCase()));

    if (!matchesSearch) return false;
    if (statusFilter === 'All') return true;
    if (statusFilter === 'Processing') {
      return o.status === 'Processing' || o.status === 'Pending';
    }
    if (statusFilter === 'in Progress' || statusFilter === 'In Progress') {
      return o.status === 'in Progress' || o.status === 'In Progress';
    }
    if (statusFilter === 'Completed') {
      return o.status === 'Completed';
    }
    if (statusFilter === 'Cancelled') {
      return o.status === 'Cancelled' || o.status === 'Canceled';
    }
    return o.status === statusFilter;
  });

  const ordersPerPage = 7;
  const totalOrdersPages = Math.max(1, Math.ceil(filteredOrders.length / ordersPerPage));
  const paginatedOrders = filteredOrders.slice(
    (currentOrdersPage - 1) * ordersPerPage,
    currentOrdersPage * ordersPerPage
  );

  const orderCounts = {
    All: orders.length,
    Processing: orders.filter((o) => o.status === 'Processing' || o.status === 'Pending').length,
    'in Progress': orders.filter((o) => o.status === 'in Progress' || o.status === 'In Progress').length,
    Completed: orders.filter((o) => o.status === 'Completed').length,
    Cancelled: orders.filter((o) => o.status === 'Cancelled' || o.status === 'Canceled').length,
  };

  const filterTabs: { label: 'All' | 'Processing' | 'in Progress' | 'Completed' | 'Cancelled'; count: number }[] = [
    { label: 'All', count: orderCounts.All },
    { label: 'Processing', count: orderCounts.Processing },
    { label: 'in Progress', count: orderCounts['in Progress'] },
    { label: 'Completed', count: orderCounts.Completed },
    { label: 'Cancelled', count: orderCounts.Cancelled },
  ];

  // Unique customer directory generated from orders
  const customerList = Array.from(
    new Map(
      orders.map((o) => [
        o.customer_name,
        {
          name: o.customer_name,
          ordersCount: orders.filter((x) => x.customer_name === o.customer_name).length,
          totalSpent: orders
            .filter((x) => x.customer_name === o.customer_name)
            .reduce((sum, x) => sum + (Number(x.total_amount) || 0), 0),
          lastVisit: o.created_at || new Date().toISOString(),
        },
      ])
    ).values()
  );

  if (authLoading) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center text-[#52c5be]">
        <Loader2 className="w-10 h-10 animate-spin" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white flex flex-col md:flex-row text-gray-950 font-sans antialiased">
      {/* Mobile Header */}
      <div className="md:hidden flex items-center justify-between p-4 bg-[#cfe8e4] border-b border-[#b7ded8]">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-lg bg-gray-950 flex items-center justify-center text-white">
            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
              <rect x="3" y="3" width="18" height="18" rx="3" />
              <circle cx="12" cy="13" r="5" />
              <path d="M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
            </svg>
          </div>
          <span className="text-xl font-bold text-gray-950">CleanOps</span>
        </div>
        <button
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="p-2 rounded-lg bg-white/70 text-gray-900"
        >
          {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Sidebar */}
      <aside
        className={`fixed md:static inset-y-0 left-0 z-40 w-64 bg-[#cfe8e4] p-6 flex flex-col justify-between transition-transform duration-300 md:translate-x-0 ${
          mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
        } shrink-0`}
      >
        <div className="space-y-8">
          {/* Logo */}
          <div className="flex items-center gap-3 px-2">
            <div className="w-9 h-9 rounded-xl bg-gray-950 flex items-center justify-center text-white shadow-sm">
              <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="3" />
                <circle cx="12" cy="13" r="5" />
                <path d="M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                <line x1="7" y1="7" x2="7.01" y2="7" />
                <line x1="10" y1="7" x2="14" y2="7" />
              </svg>
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-gray-950">CleanOps</h1>
          </div>

          {/* Navigation Links */}
          <nav className="space-y-1.5">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.label;
              return (
                <button
                  key={item.label}
                  onClick={() => {
                    setActiveTab(item.label);
                    setMobileMenuOpen(false);
                  }}
                  className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl text-base font-semibold transition-all cursor-pointer ${
                    isActive
                      ? 'bg-[#52c5be] text-gray-950 shadow-sm'
                      : 'text-gray-900 hover:bg-[#bde1db]'
                  }`}
                >
                  <Icon className="w-5 h-5 stroke-[2.2]" />
                  <span>{item.label}</span>
                </button>
              );
            })}
          </nav>
        </div>

        {/* Sidebar Footer User Profile */}
        <div className="pt-4 border-t border-[#b6ded7]">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-full bg-gray-950 flex items-center justify-center text-white">
                <User className="w-5 h-5" />
              </div>
              <div>
                <p className="text-base font-bold text-gray-950 leading-tight">Admin</p>
                <p className="text-xs text-gray-600 truncate max-w-[110px]">
                  {profile?.username || user?.email?.split('@')[0] || 'CleanOps'}
                </p>
              </div>
            </div>
            <button
              onClick={signOut}
              title="Sign Out"
              className="p-2 rounded-lg text-gray-700 hover:text-red-600 hover:bg-white/60 transition-colors cursor-pointer"
            >
              <LogOut className="w-4 h-4" />
            </button>
          </div>
        </div>
      </aside>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0 bg-white">
        {/* Top Bar */}
        <header className="h-20 px-6 sm:px-10 flex items-center justify-between border-b border-gray-100 bg-white relative">
          {/* Search Bar */}
          <div className="relative w-full max-w-xl">
            <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
              <Search className="w-5 h-5 stroke-[2]" />
            </div>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search orders, customers, or services..."
              className="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm placeholder-gray-400 text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20 transition-all"
            />
          </div>

          {/* Right Icons */}
          <div className="flex items-center gap-4 sm:gap-5 ml-4 text-gray-900">
            {/* Notifications Icon & Popover */}
            <div className="relative">
              <button
                onClick={() => setShowNotificationsDropdown(!showNotificationsDropdown)}
                className="p-2 hover:bg-gray-100 rounded-full transition-colors cursor-pointer relative"
              >
                <Bell className="w-6 h-6 stroke-[2]" />
                <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-[#52c5be] rounded-full ring-2 ring-white"></span>
              </button>

              {showNotificationsDropdown && (
                <div className="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 p-4 z-30">
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                    <h4 className="text-sm font-bold text-gray-950">Notifications</h4>
                    <span className="text-[11px] font-semibold text-[#3bb7b0]">Mark all read</span>
                  </div>
                  <div className="space-y-2.5 max-h-72 overflow-y-auto">
                    {sampleNotifications.map((n) => (
                      <div key={n.id} className="p-2.5 rounded-xl bg-gray-50 hover:bg-[#cfe8e4]/40 transition-colors">
                        <div className="flex items-center justify-between">
                          <p className="text-xs font-bold text-gray-950">{n.title}</p>
                          <span className="text-[10px] text-gray-400">{n.time}</span>
                        </div>
                        <p className="text-xs text-gray-600 mt-0.5">{n.desc}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Messages Icon */}
            <button
              onClick={() => setActiveTab('Notifications')}
              className="p-2 hover:bg-gray-100 rounded-full transition-colors cursor-pointer"
            >
              <MessageSquare className="w-6 h-6 stroke-[2]" />
            </button>

            {/* Profile Avatar */}
            <div
              onClick={() => setActiveTab('Settings')}
              className="w-9 h-9 rounded-full bg-gray-950 flex items-center justify-center text-white cursor-pointer hover:opacity-90"
              title="Admin Profile & Settings"
            >
              <User className="w-5 h-5" />
            </div>
          </div>
        </header>

        {/* Dashboard Main Canvas */}
        <main className="flex-1 p-6 sm:p-10 overflow-y-auto space-y-6">
          {/* TAB 1: DASHBOARD (Exact Mockup Design) */}
          {activeTab === 'Dashboard' && (
            <>
              {/* Greeting & Date Header */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Good morning, Admin!
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Here’s what’s happening with your laundry shop today.
                  </p>
                </div>

                {/* Date Pill Badge */}
                <div className="self-start sm:self-auto bg-[#cfe8e4] text-gray-900 font-semibold px-5 py-2.5 rounded-xl text-sm shadow-none">
                  {currentDate}
                </div>
              </div>

              {/* 4 Stat Cards Row */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                {/* Card 1: Total Orders */}
                <div
                  onClick={() => {
                    setStatusFilter('All');
                    setActiveTab('Orders');
                  }}
                  className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between transition-transform hover:-translate-y-0.5 duration-200 cursor-pointer"
                >
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Receipt className="w-5 h-5 stroke-[2.2]" />
                    <span>Total Orders</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {metrics.total}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">All recorded orders</span>
                  </div>
                </div>

                {/* Card 2: In Progress */}
                <div
                  onClick={() => {
                    setStatusFilter('In Progress');
                    setActiveTab('Orders');
                  }}
                  className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between transition-transform hover:-translate-y-0.5 duration-200 cursor-pointer"
                >
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Clock className="w-5 h-5 stroke-[2.2]" />
                    <span>In Progress</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {metrics.inProgress}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Currently in wash & dry</span>
                  </div>
                </div>

                {/* Card 3: Completed */}
                <div
                  onClick={() => {
                    setStatusFilter('Completed');
                    setActiveTab('Orders');
                  }}
                  className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between transition-transform hover:-translate-y-0.5 duration-200 cursor-pointer"
                >
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <CheckCircle2 className="w-5 h-5 stroke-[2.2]" />
                    <span>Completed</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {metrics.completed}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Ready for pickup & delivered</span>
                  </div>
                </div>

                {/* Card 4: Canceled */}
                <div
                  onClick={() => {
                    setStatusFilter('Cancelled');
                    setActiveTab('Orders');
                  }}
                  className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between transition-transform hover:-translate-y-0.5 duration-200 cursor-pointer"
                >
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <FileX className="w-5 h-5 stroke-[2.2]" />
                    <span>Canceled</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {metrics.canceled}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Voided or cancelled orders</span>
                  </div>
                </div>
              </div>

              {/* Bottom Grid: Orders Overview & Recent Orders */}
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {/* Orders Overview Card (7 cols) */}
                <div className="lg:col-span-7 bg-[#cfe8e4] rounded-2xl p-6 sm:p-8 flex flex-col justify-between min-h-[380px]">
                  <div className="flex items-center justify-between mb-6">
                    <h3 className="text-xl font-bold text-gray-950">Orders Overview</h3>

                    {/* Dropdown Filter */}
                    <div className="relative">
                      <button
                        onClick={() => setShowTimeframeDropdown(!showTimeframeDropdown)}
                        className="bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold px-4 py-2 rounded-xl text-sm flex items-center gap-2 shadow-sm transition-all cursor-pointer"
                      >
                        <span>{timeframe}</span>
                        <ChevronDown className="w-4 h-4 stroke-[2.5]" />
                      </button>

                      {showTimeframeDropdown && (
                        <div className="absolute right-0 mt-2 w-36 bg-white rounded-xl shadow-lg border border-gray-100 py-1.5 z-20">
                          {(['Last 7 days', 'Last 30 days', 'This Month'] as const).map((tf) => (
                            <button
                              key={tf}
                              onClick={() => {
                                setTimeframe(tf);
                                setShowTimeframeDropdown(false);
                              }}
                              className={`w-full text-left px-3.5 py-2 text-xs font-semibold hover:bg-gray-100 ${
                                timeframe === tf ? 'text-[#3bb7b0]' : 'text-gray-800'
                              }`}
                            >
                              {tf}
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Chart Visualization */}
                  <div className="flex-1 flex flex-col justify-end pt-4 pb-2">
                    <div className="h-44 w-full flex items-end justify-between gap-3 sm:gap-6 px-2 sm:px-6">
                      {chartDays.map((item) => (
                        <div key={item.day} className="flex-1 flex flex-col items-center gap-2.5 h-full justify-end group">
                          <span className="text-[11px] font-bold text-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">
                            {item.count}
                          </span>
                          <div
                            style={{ height: item.height }}
                            className="w-full max-w-[38px] bg-[#52c5be] hover:bg-[#3fb8b1] rounded-t-xl transition-all duration-300 shadow-sm"
                          />
                          <span className="text-xs font-bold text-gray-800 mt-1">{item.day}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                {/* Recent Orders Card (5 cols) */}
                <div className="lg:col-span-5 bg-[#cfe8e4] rounded-2xl p-6 sm:p-8 flex flex-col justify-between min-h-[380px]">
                  <div className="flex items-center justify-between mb-5">
                    <h3 className="text-xl font-bold text-gray-950">Recent Orders</h3>
                    <button
                      onClick={() => setShowCreateOrderModal(true)}
                      className="p-1.5 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1 shadow-sm"
                    >
                      <Plus className="w-3.5 h-3.5" />
                      <span>New</span>
                    </button>
                  </div>

                  {/* Inner Table Container */}
                  <div className="bg-[#a2cdc7] rounded-xl p-4 sm:p-5 flex-1 flex flex-col justify-between">
                    {loading ? (
                      <div className="py-12 flex justify-center text-gray-800">
                        <Loader2 className="w-7 h-7 animate-spin" />
                      </div>
                    ) : (
                      <div className="overflow-x-auto w-full">
                        <table className="w-full text-left">
                          <thead>
                            <tr className="border-b border-[#7fb5af] text-sm font-bold text-gray-950">
                              <th className="pb-3 px-1">Orders #</th>
                              <th className="pb-3 px-2">Customer</th>
                              <th className="pb-3 px-1 text-right">Status</th>
                            </tr>
                          </thead>
                          <tbody className="divide-y divide-[#8ec2bc]">
                            {filteredOrders.slice(0, 5).map((order) => (
                              <tr
                                key={order.id}
                                onClick={() => setSelectedOrder(order)}
                                className="hover:bg-white/15 transition-colors cursor-pointer"
                              >
                                <td className="py-3 px-1 text-sm font-bold text-gray-950">
                                  #{order.id}
                                </td>
                                <td className="py-3 px-2 text-sm font-medium text-gray-900 truncate max-w-[120px]">
                                  {order.customer_name}
                                </td>
                                <td className="py-3 px-1 text-right">
                                  <span
                                    className={`inline-block px-2.5 py-0.5 rounded-md text-xs font-bold ${
                                      order.status === 'Completed'
                                        ? 'bg-white/80 text-emerald-800'
                                        : order.status === 'In Progress'
                                        ? 'bg-[#52c5be] text-gray-950'
                                        : 'bg-white/70 text-gray-900'
                                    }`}
                                  >
                                    {order.status}
                                  </span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </>
          )}

          {/* TAB 2: ORDERS */}
          {activeTab === 'Orders' && (
            <div className="space-y-6 w-full">
              {/* Header */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Orders
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5 font-normal">
                    Manage and track all laundry orders.
                  </p>
                </div>
                <button
                  onClick={() => setShowCreateOrderModal(true)}
                  className="bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold px-6 py-2.5 rounded-xl text-sm flex items-center gap-1.5 shadow-none transition-all cursor-pointer self-start sm:self-auto"
                >
                  <Plus className="w-4 h-4 stroke-[3]" />
                  <span>New Order</span>
                </button>
              </div>

              {/* Status Filters */}
              <div className="flex items-center gap-3 overflow-x-auto pb-1">
                {filterTabs.map((filter) => {
                  const isCurrent =
                    statusFilter === filter.label ||
                    (filter.label === 'in Progress' && statusFilter === 'In Progress');
                  return (
                    <button
                      key={filter.label}
                      onClick={() => {
                        setStatusFilter(filter.label);
                        setCurrentOrdersPage(1);
                      }}
                      className={`px-5 py-2 rounded-xl text-sm font-bold transition-all cursor-pointer whitespace-nowrap ${
                        isCurrent
                          ? 'bg-[#52c5be] text-gray-950 shadow-none'
                          : 'bg-[#cfe8e4] text-gray-800 hover:bg-[#bfe1dc]'
                      }`}
                    >
                      {filter.label} ({filter.count})
                    </button>
                  );
                })}
              </div>

              {/* Orders Table Container */}
              <div className="bg-[#cfe8e4] rounded-2xl overflow-hidden shadow-none border border-[#b4ded7]">
                <div className="overflow-x-auto">
                  <table className="w-full text-left border-collapse">
                    <thead>
                      <tr className="border-b border-[#7ea9a2] text-gray-950 font-bold text-sm sm:text-base bg-[#cfe8e4]">
                        <th className="py-4 px-6 font-bold">Order #</th>
                        <th className="py-4 px-6 font-bold">Customer</th>
                        <th className="py-4 px-6 font-bold">Service</th>
                        <th className="py-4 px-6 font-bold">Status</th>
                        <th className="py-4 px-6 font-bold">Date</th>
                        <th className="py-4 px-6 font-bold">Total</th>
                        <th className="py-4 px-6 font-bold text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {paginatedOrders.length === 0 ? (
                        <tr>
                          <td colSpan={7} className="py-12 text-center text-gray-600 font-medium bg-[#cfe8e4]">
                            No orders found matching the selected filter.
                          </td>
                        </tr>
                      ) : (
                        paginatedOrders.map((order, idx) => (
                          <tr
                            key={order.id}
                            className={`transition-colors text-sm sm:text-base ${
                              idx % 2 === 0 ? 'bg-[#cfe8e4]' : 'bg-[#a3d5cc]'
                            } hover:bg-[#92cbbf]/60`}
                          >
                            <td className="py-3.5 px-6 font-bold text-gray-950">#{order.id}</td>
                            <td className="py-3.5 px-6 font-semibold text-gray-900">{order.customer_name}</td>
                            <td className="py-3.5 px-6 text-gray-800 text-sm font-medium">{order.category || 'Wash & Fold'}</td>
                            <td className="py-3.5 px-6">
                              <span
                                className={`text-xs font-bold px-3 py-1 rounded-md inline-block ${
                                  order.status === 'Completed'
                                    ? 'bg-emerald-100 text-emerald-900 border border-emerald-300'
                                    : order.status === 'in Progress' || order.status === 'In Progress'
                                    ? 'bg-[#52c5be] text-gray-950 font-bold'
                                    : order.status === 'Processing' || order.status === 'Pending'
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                    : 'bg-rose-100 text-rose-900 border border-rose-300'
                                }`}
                              >
                                {order.status}
                              </span>
                            </td>
                            <td className="py-3.5 px-6 text-gray-800 text-sm font-medium">
                              {order.created_at
                                ? new Date(order.created_at).toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                  })
                                : 'Sep 24, 2026'}
                            </td>
                            <td className="py-3.5 px-6 font-bold text-gray-950">
                              ₱{Number(order.total_amount || 0).toFixed(2)}
                            </td>
                            <td className="py-3.5 px-6 text-center">
                              <button
                                onClick={() => setSelectedOrder(order)}
                                className="px-3.5 py-1.5 bg-white/95 hover:bg-white text-gray-900 rounded-lg text-xs font-bold shadow-sm transition-all cursor-pointer"
                              >
                                Inspect
                              </button>
                            </td>
                          </tr>
                        ))
                      )}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Pagination */}
              <div className="flex items-center justify-end gap-2 pt-1">
                <button
                  onClick={() => setCurrentOrdersPage((p) => Math.max(1, p - 1))}
                  disabled={currentOrdersPage === 1}
                  className="w-8 h-8 rounded-full bg-gray-950 hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center transition-colors cursor-pointer shadow-sm"
                  title="Previous Page"
                >
                  <ChevronLeft className="w-4 h-4 stroke-[2.5]" />
                </button>

                {Array.from({ length: totalOrdersPages }, (_, i) => i + 1).map((page) => (
                  <button
                    key={page}
                    onClick={() => setCurrentOrdersPage(page)}
                    className={`w-8 h-8 rounded-full text-xs font-bold flex items-center justify-center transition-all cursor-pointer shadow-sm ${
                      currentOrdersPage === page
                        ? 'bg-gray-950 text-white ring-2 ring-gray-950/20'
                        : 'bg-gray-950/80 hover:bg-gray-950 text-white'
                    }`}
                  >
                    {page}
                  </button>
                ))}

                <button
                  onClick={() => setCurrentOrdersPage((p) => Math.min(totalOrdersPages, p + 1))}
                  disabled={currentOrdersPage === totalOrdersPages}
                  className="w-8 h-8 rounded-full bg-gray-950 hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed text-white flex items-center justify-center transition-colors cursor-pointer shadow-sm"
                  title="Next Page"
                >
                  <ChevronRight className="w-4 h-4 stroke-[2.5]" />
                </button>
              </div>
            </div>
          )}

          {/* TAB 3: CUSTOMERS */}
          {activeTab === 'Customers' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Customer Directory
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Track active laundry patrons and load history.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  {customerList.length} Total Customers
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
                {customerList.map((customer) => (
                  <div key={customer.name} className="bg-[#cfe8e4] rounded-2xl p-6 space-y-3">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-gray-950 flex items-center justify-center text-white font-bold">
                        {customer.name.charAt(0)}
                      </div>
                      <div>
                        <h4 className="font-bold text-gray-950 text-base">{customer.name}</h4>
                        <span className="text-xs text-gray-600 font-medium">Regular Client</span>
                      </div>
                    </div>
                    <div className="pt-2 border-t border-[#b7ded8] flex justify-between text-xs font-semibold text-gray-700">
                      <span>Total Loads: {customer.ordersCount}</span>
                      <span>Spent: ₱{customer.totalSpent.toLocaleString()}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* TAB: STAFF & TEAM MANAGEMENT */}
          {activeTab === 'Staff' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Staff & Team Management
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Manage staff roles, shift schedules, and laundry shop attendants.
                  </p>
                </div>

                <button
                  onClick={() => setShowAddStaffModal(true)}
                  className="self-start sm:self-auto bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shadow-sm transition-all cursor-pointer"
                >
                  <Plus className="w-4 h-4 stroke-[2.5]" />
                  <span>Add Staff Member</span>
                </button>
              </div>

              {/* Staff Summary Cards Row */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Users className="w-5 h-5 stroke-[2.2]" />
                    <span>Total Staff</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {staffList.length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Registered shop personnel</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <CheckCircle2 className="w-5 h-5 stroke-[2.2]" />
                    <span>Active On Duty</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-emerald-800 tracking-tight">
                      {staffList.filter((s) => s.status === 'Active').length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Currently handling laundry</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Briefcase className="w-5 h-5 stroke-[2.2]" />
                    <span>Supervisors</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {staffList.filter((s) => s.role === 'Supervisor').length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Shift managers & leads</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Clock className="w-5 h-5 stroke-[2.2]" />
                    <span>On Leave</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-amber-800 tracking-tight">
                      {staffList.filter((s) => s.status === 'On Leave').length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Excused or off schedule</span>
                  </div>
                </div>
              </div>

              {/* Role Filter Tabs */}
              <div className="flex flex-wrap items-center gap-2">
                {['All', 'Supervisor', 'Laundry Attendant', 'Washer', 'Dryer Specialist'].map((role) => (
                  <button
                    key={role}
                    onClick={() => setStaffRoleFilter(role)}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                      staffRoleFilter === role
                        ? 'bg-gray-950 text-white shadow-sm'
                        : 'bg-[#cfe8e4] text-gray-800 hover:bg-[#bde1db]'
                    }`}
                  >
                    {role}
                  </button>
                ))}
              </div>

              {/* Staff Cards Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {staffList
                  .filter((s) => (staffRoleFilter === 'All' ? true : s.role === staffRoleFilter))
                  .map((staff) => (
                    <div key={staff.id} className="bg-[#cfe8e4] rounded-2xl p-6 flex flex-col justify-between space-y-4">
                      <div>
                        <div className="flex items-center justify-between mb-3">
                          <div className="flex items-center gap-3">
                            <div className="w-11 h-11 rounded-xl bg-gray-950 flex items-center justify-center text-white font-extrabold text-sm">
                              {staff.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .slice(0, 2)}
                            </div>
                            <div>
                              <h4 className="font-bold text-gray-950 text-base leading-tight">{staff.name}</h4>
                              <span className="inline-block mt-0.5 text-xs font-semibold px-2 py-0.5 rounded-md bg-[#52c5be] text-gray-950">
                                {staff.role}
                              </span>
                            </div>
                          </div>

                          <button
                            onClick={() => handleToggleStaffStatus(staff.id)}
                            title="Click to toggle status"
                            className={`text-xs font-bold px-2.5 py-1 rounded-full cursor-pointer transition-colors ${
                              staff.status === 'Active'
                                ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                                : 'bg-amber-100 text-amber-800 hover:bg-amber-200'
                            }`}
                          >
                            {staff.status}
                          </button>
                        </div>

                        <div className="space-y-1.5 text-xs text-gray-700">
                          <div className="flex items-center gap-2">
                            <Mail className="w-3.5 h-3.5 text-gray-500" />
                            <span>{staff.email}</span>
                          </div>
                          <div className="flex items-center gap-2">
                            <Phone className="w-3.5 h-3.5 text-gray-500" />
                            <span>{staff.phone}</span>
                          </div>
                          <div className="flex items-center gap-2 text-gray-500">
                            <Clock className="w-3.5 h-3.5" />
                            <span>Joined {staff.joinedDate}</span>
                          </div>
                        </div>

                        <div className="mt-4 pt-3 border-t border-[#b7ded8]">
                          <span className="text-[11px] font-bold text-gray-600 uppercase tracking-wider block mb-2">
                            Work Schedule
                          </span>
                          <div className="flex flex-wrap gap-1.5">
                            {staff.schedule.map((day) => (
                              <span
                                key={day}
                                className="px-2 py-0.5 bg-white/70 text-gray-800 rounded-md text-[11px] font-semibold"
                              >
                                {day}
                              </span>
                            ))}
                          </div>
                        </div>
                      </div>

                      <div className="pt-3 border-t border-[#b7ded8] flex items-center justify-between">
                        <button
                          onClick={() => handleToggleStaffStatus(staff.id)}
                          className="text-xs font-bold text-gray-800 hover:underline cursor-pointer"
                        >
                          {staff.status === 'Active' ? 'Mark On Leave' : 'Set Active'}
                        </button>

                        <button
                          onClick={() => handleDeleteStaff(staff.id)}
                          className="text-gray-600 hover:text-red-600 transition-colors p-1.5 rounded-lg hover:bg-white/50 cursor-pointer"
                          title="Remove staff member"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  ))}
              </div>
            </div>
          )}

          {/* TAB: STOCK & INVENTORY MANAGEMENT */}
          {activeTab === 'Inventory' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Stock & Inventory
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Monitor detergents, softeners, packaging, and supply levels.
                  </p>
                </div>

                <button
                  onClick={() => setShowAddInventoryModal(true)}
                  className="self-start sm:self-auto bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shadow-sm transition-all cursor-pointer"
                >
                  <Plus className="w-4 h-4 stroke-[2.5]" />
                  <span>New Stock Item</span>
                </button>
              </div>

              {/* Low Stock Warning Banner */}
              {inventoryList.some((item) => item.quantity <= item.minStock) && (
                <div className="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3 text-amber-900">
                  <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0" />
                  <div className="text-xs sm:text-sm font-medium">
                    <span className="font-bold">Attention Needed:</span> Some essential supplies (
                    {inventoryList
                      .filter((i) => i.quantity <= i.minStock)
                      .map((i) => i.name)
                      .join(', ')}
                    ) are running below safety threshold!
                  </div>
                </div>
              )}

              {/* Stats Row */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Package className="w-5 h-5 stroke-[2.2]" />
                    <span>Total SKUs</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {inventoryList.length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Cataloged supplies</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <AlertTriangle className="w-5 h-5 stroke-[2.2]" />
                    <span>Low Stock Alerts</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-amber-800 tracking-tight">
                      {inventoryList.filter((i) => i.quantity <= i.minStock).length}
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Below safety threshold</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Sparkles className="w-5 h-5 stroke-[2.2]" />
                    <span>Detergents Stock</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      {inventoryList
                        .filter((i) => i.category === 'Detergent' || i.category === 'Softener')
                        .reduce((sum, i) => sum + i.quantity, 0)}{' '}
                      <span className="text-lg font-bold">kg/L</span>
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Powders & conditioners</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Tag className="w-5 h-5 stroke-[2.2]" />
                    <span>Packaging Bags</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-emerald-800 tracking-tight">
                      {inventoryList.find((i) => i.category === 'Packaging')?.quantity || 0}{' '}
                      <span className="text-lg font-bold">pcs</span>
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Ready for customer loads</span>
                  </div>
                </div>
              </div>

              {/* Stock Cards Grid */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {inventoryList.map((item) => {
                  const isLow = item.quantity <= item.minStock;
                  return (
                    <div
                      key={item.id}
                      className={`rounded-2xl p-6 flex flex-col justify-between space-y-4 border transition-all ${
                        isLow ? 'bg-amber-50/70 border-amber-200' : 'bg-[#cfe8e4] border-transparent'
                      }`}
                    >
                      <div>
                        <div className="flex items-center justify-between mb-2">
                          <div className="flex items-center gap-2">
                            <Package className="w-5 h-5 text-[#2a9891]" />
                            <h4 className="font-bold text-gray-950 text-base">{item.name}</h4>
                          </div>
                          <span className="text-[11px] font-bold px-2 py-0.5 rounded-md bg-white/80 text-gray-700">
                            {item.category}
                          </span>
                        </div>

                        <div className="my-4 flex items-baseline gap-2">
                          <span
                            className={`text-4xl font-extrabold tracking-tight ${
                              isLow ? 'text-amber-700' : 'text-gray-950'
                            }`}
                          >
                            {item.quantity}
                          </span>
                          <span className="text-sm font-semibold text-gray-600">{item.unit}</span>
                          {isLow && (
                            <span className="ml-auto text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">
                              Low Stock (≤{item.minStock})
                            </span>
                          )}
                        </div>
                      </div>

                      {/* Controls like prototype stock.php */}
                      <div className="pt-3 border-t border-[#b7ded8] flex items-center justify-between gap-2">
                        <button
                          onClick={() => handleUpdateStock(item.id, -1)}
                          className="w-9 h-9 rounded-xl bg-white hover:bg-gray-100 flex items-center justify-center font-bold text-gray-800 shadow-xs cursor-pointer"
                          title="Reduce 1"
                        >
                          <Minus className="w-4 h-4" />
                        </button>

                        <button
                          onClick={() => handleUpdateStock(item.id, 10)}
                          className="flex-1 py-2 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-xs transition-colors shadow-xs cursor-pointer"
                        >
                          Restock +10
                        </button>

                        <button
                          onClick={() => handleUpdateStock(item.id, 1)}
                          className="w-9 h-9 rounded-xl bg-white hover:bg-gray-100 flex items-center justify-center font-bold text-gray-800 shadow-xs cursor-pointer"
                          title="Add 1"
                        >
                          <Plus className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* TAB 4: SERVICES CATALOG */}
          {activeTab === 'Services' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Laundry Services
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Configure pricing rates, turnaround times, and offerings.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  {servicesCatalog.length} Active Services
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {servicesCatalog.map((s) => (
                  <div key={s.name} className="bg-[#cfe8e4] rounded-2xl p-6 flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-2">
                        <h4 className="font-bold text-gray-950 text-lg">{s.name}</h4>
                        <span
                          className={`text-xs font-bold px-2.5 py-0.5 rounded-full ${
                            s.active ? 'bg-[#52c5be] text-gray-950' : 'bg-gray-200 text-gray-600'
                          }`}
                        >
                          {s.active ? 'Active' : 'Paused'}
                        </span>
                      </div>
                      <p className="text-2xl font-extrabold text-gray-950 mt-2">{s.price}</p>
                    </div>
                    <div className="pt-4 border-t border-[#b7ded8] mt-4 flex items-center justify-between text-xs font-medium text-gray-600">
                      <span>Turnaround: {s.turnAround}</span>
                      <button className="text-xs font-bold text-[#2a9891] hover:underline cursor-pointer">
                        Edit Rate
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* TAB 5: MACHINE SCHEDULE */}
          {activeTab === 'Schedule' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Machine Queue & Schedule
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Live wash & dry cycles across shop appliances.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  {machinesSchedule.filter((m) => m.status !== 'Available').length} In Operation
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {machinesSchedule.map((m) => (
                  <div key={m.id} className="bg-[#cfe8e4] rounded-2xl p-6 space-y-4">
                    <div className="flex items-center justify-between">
                      <h4 className="font-bold text-gray-950 text-base">{m.id}</h4>
                      <span
                        className={`text-xs font-bold px-2.5 py-1 rounded-md ${
                          m.status === 'Available'
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-[#52c5be] text-gray-950'
                        }`}
                      >
                        {m.status}
                      </span>
                    </div>
                    <div className="space-y-1">
                      <p className="text-xs text-gray-500 uppercase tracking-wider font-semibold">Active Cycle</p>
                      <p className="text-sm font-bold text-gray-900">{m.customer}</p>
                    </div>
                    <div className="pt-2 border-t border-[#b7ded8] flex justify-between text-xs font-semibold text-gray-700">
                      <span>Time Remaining:</span>
                      <span className="font-bold text-gray-950">{m.remaining}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* TAB 6: REPORTS & FINANCIALS */}
          {activeTab === 'Reports' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Performance & Financials
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Revenue breakdowns, laundry load trends, and analytics.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  September 2026 Summary
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <DollarSign className="w-5 h-5 stroke-[2.2]" />
                    <span>Total Monthly Revenue</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      ₱48,920.00
                    </span>
                    <span className="text-xs text-emerald-800 block mt-1 font-semibold">+14.2% from last month</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <TrendingUp className="w-5 h-5 stroke-[2.2]" />
                    <span>Avg. Order Value</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      ₱465.00
                    </span>
                    <span className="text-xs text-gray-600 block mt-1 font-medium">Across 105 loads</span>
                  </div>
                </div>
                <div className="bg-[#cfe8e4] rounded-2xl p-5 sm:p-6 min-h-[145px] flex flex-col justify-between">
                  <div className="flex items-center gap-2.5 text-gray-950 font-bold text-base">
                    <Receipt className="w-5 h-5 stroke-[2.2]" />
                    <span>Total Kilograms Washed</span>
                  </div>
                  <div className="mt-4">
                    <span className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                      1,240 kg
                    </span>
                    <span className="text-xs text-emerald-800 block mt-1 font-semibold">99.2% on-time turnover</span>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* TAB 7: NOTIFICATIONS CENTER */}
          {activeTab === 'Notifications' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Notification Center
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    System logs, machine triggers, and customer booking alerts.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  {sampleNotifications.length} Recent Alerts
                </div>
              </div>

              <div className="bg-[#cfe8e4] rounded-2xl p-6 divide-y divide-[#b7ded8]">
                {sampleNotifications.map((n) => (
                  <div key={n.id} className="py-4 flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3.5">
                      <div className="w-10 h-10 rounded-xl bg-[#52c5be] flex items-center justify-center text-gray-950 shrink-0 mt-0.5">
                        <Bell className="w-5 h-5" />
                      </div>
                      <div>
                        <h4 className="font-bold text-gray-950 text-base">{n.title}</h4>
                        <p className="text-sm text-gray-700 mt-0.5">{n.desc}</p>
                      </div>
                    </div>
                    <span className="text-xs text-gray-500 font-medium whitespace-nowrap">{n.time}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* TAB 8: SHOP SETTINGS */}
          {activeTab === 'Settings' && (
            <div className="space-y-6 w-full">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-gray-950 tracking-tight">
                    Shop Settings
                  </h2>
                  <p className="text-gray-500 text-sm sm:text-base mt-1.5">
                    Configure your laundry shop profile and contact information.
                  </p>
                </div>
                <div className="bg-[#cfe8e4] px-5 py-2.5 rounded-xl text-sm font-bold text-gray-900 self-start sm:self-auto">
                  Active Shop Profile
                </div>
              </div>

              {settingsSaved && (
                <div className="p-4 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 text-sm font-semibold flex items-center gap-2 animate-fadeIn">
                  <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                  <span>Shop details saved successfully!</span>
                </div>
              )}

              <form onSubmit={handleSaveSettings} className="bg-[#cfe8e4] rounded-2xl p-6 sm:p-8 space-y-6 w-full">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">
                      Shop Name
                    </label>
                    <input
                      type="text"
                      required
                      value={shopName}
                      onChange={(e) => setShopName(e.target.value)}
                      className="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">
                      Contact Phone Number
                    </label>
                    <input
                      type="tel"
                      required
                      value={shopContact}
                      onChange={(e) => setShopContact(e.target.value)}
                      className="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold uppercase tracking-wider text-gray-800 mb-2">
                    Shop Address
                  </label>
                  <input
                    type="text"
                    required
                    value={shopAddress}
                    onChange={(e) => setShopAddress(e.target.value)}
                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    className="px-6 py-3.5 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-sm shadow-sm transition-all cursor-pointer flex items-center gap-2"
                  >
                    <Save className="w-4 h-4 stroke-[2.5]" />
                    <span>Save Changes</span>
                  </button>
                </div>
              </form>
            </div>
          )}
        </main>
      </div>

      {/* CREATE ORDER MODAL */}
      {showCreateOrderModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fadeIn">
          <div className="w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-gray-100">
            <button
              onClick={() => setShowCreateOrderModal(false)}
              className="absolute top-5 right-5 p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3 mb-5">
              <div className="w-10 h-10 rounded-2xl bg-[#cfe8e4] flex items-center justify-center text-gray-950">
                <Receipt className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-950">Create Laundry Order</h3>
                <p className="text-xs text-gray-500">Record a new load for your laundry facility</p>
              </div>
            </div>

            <form onSubmit={handleCreateOrder} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1.5">Customer Name</label>
                <input
                  type="text"
                  required
                  value={newCustomerName}
                  onChange={(e) => setNewCustomerName(e.target.value)}
                  placeholder="e.g. Elena Gilbert"
                  className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Service Category</label>
                  <select
                    value={newCategory}
                    onChange={(e) => setNewCategory(e.target.value)}
                    className="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="Wash & Fold">Wash & Fold</option>
                    <option value="Dry Cleaning">Dry Cleaning</option>
                    <option value="Express Wash">Express Wash</option>
                    <option value="Beddings & Linen">Beddings & Linen</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Total Amount (₱)</label>
                  <input
                    type="number"
                    required
                    value={newAmount}
                    onChange={(e) => setNewAmount(e.target.value)}
                    className="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Initial Status</label>
                  <select
                    value={newStatus}
                    onChange={(e) => setNewStatus(e.target.value as any)}
                    className="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Pending">Pending</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Payment</label>
                  <select
                    value={newPayment}
                    onChange={(e) => setNewPayment(e.target.value as any)}
                    className="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="Paid">Paid</option>
                    <option value="Unpaid">Unpaid</option>
                  </select>
                </div>
              </div>

              <button
                type="submit"
                className="w-full py-3 px-4 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-sm shadow-sm transition-all cursor-pointer mt-3"
              >
                Save & Add Order
              </button>
            </form>
          </div>
        </div>
      )}

      {/* ORDER DETAILS & STATUS UPDATER MODAL */}
      {selectedOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fadeIn">
          <div className="w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl relative border border-gray-100 space-y-5">
            <button
              onClick={() => setSelectedOrder(null)}
              className="absolute top-5 right-5 p-1.5 rounded-full hover:bg-gray-100 text-gray-400 hover:text-gray-700 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-2xl bg-[#cfe8e4] flex items-center justify-center text-gray-950 font-bold">
                #{selectedOrder.id}
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-950">{selectedOrder.customer_name}</h3>
                <span className="text-xs text-gray-500 font-medium">{selectedOrder.category || 'General Wash'}</span>
              </div>
            </div>

            <div className="p-4 rounded-2xl bg-[#cfe8e4]/60 space-y-2.5">
              <div className="flex justify-between text-xs font-semibold text-gray-700">
                <span>Order Status:</span>
                <span className="font-bold text-gray-950 uppercase">{selectedOrder.status}</span>
              </div>
              <div className="flex justify-between text-xs font-semibold text-gray-700">
                <span>Total Amount:</span>
                <span className="font-bold text-gray-950">₱{Number(selectedOrder.total_amount || 0).toFixed(2)}</span>
              </div>
              <div className="flex justify-between text-xs font-semibold text-gray-700">
                <span>Payment:</span>
                <span className="font-bold text-emerald-800">{selectedOrder.payment_status || 'Paid'}</span>
              </div>
            </div>

            <div className="space-y-2">
              <p className="text-xs font-bold uppercase tracking-wider text-gray-600">Quick Update Status:</p>
              <div className="grid grid-cols-3 gap-2">
                <button
                  onClick={() => handleUpdateOrderStatus(selectedOrder.id, 'In Progress')}
                  className={`py-2 px-2 text-xs font-bold rounded-xl transition-all cursor-pointer ${
                    selectedOrder.status === 'In Progress'
                      ? 'bg-[#52c5be] text-gray-950 ring-2 ring-gray-950/20'
                      : 'bg-gray-100 hover:bg-gray-200 text-gray-800'
                  }`}
                >
                  In Progress
                </button>
                <button
                  onClick={() => handleUpdateOrderStatus(selectedOrder.id, 'Completed')}
                  className={`py-2 px-2 text-xs font-bold rounded-xl transition-all cursor-pointer ${
                    selectedOrder.status === 'Completed'
                      ? 'bg-emerald-500 text-white ring-2 ring-emerald-600'
                      : 'bg-gray-100 hover:bg-gray-200 text-gray-800'
                  }`}
                >
                  Completed
                </button>
                <button
                  onClick={() => handleUpdateOrderStatus(selectedOrder.id, 'Cancelled')}
                  className={`py-2 px-2 text-xs font-bold rounded-xl transition-all cursor-pointer ${
                    selectedOrder.status === 'Cancelled'
                      ? 'bg-red-500 text-white ring-2 ring-red-600'
                      : 'bg-gray-100 hover:bg-gray-200 text-gray-800'
                  }`}
                >
                  Cancel
                </button>
              </div>
            </div>

            <button
              onClick={() => setSelectedOrder(null)}
              className="w-full py-2.5 bg-gray-950 hover:bg-gray-800 text-white font-bold rounded-xl text-xs transition-colors cursor-pointer"
            >
              Done
            </button>
          </div>
        </div>
      )}

      {/* MODAL 3: ADD NEW STAFF MEMBER */}
      {showAddStaffModal && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-xl font-extrabold text-gray-950">Add Staff Member</h3>
                <p className="text-xs text-gray-500 mt-0.5">Register a new team member to your laundry shop.</p>
              </div>
              <button
                onClick={() => setShowAddStaffModal(false)}
                className="p-1 rounded-full text-gray-400 hover:text-gray-800 cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddStaff} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1.5">Full Name</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Juan Perez"
                  value={newStaffName}
                  onChange={(e) => setNewStaffName(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Email Address</label>
                  <input
                    type="email"
                    placeholder="staff@cleanops.com"
                    value={newStaffEmail}
                    onChange={(e) => setNewStaffEmail(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Phone Number</label>
                  <input
                    type="text"
                    placeholder="0917-123-4567"
                    value={newStaffPhone}
                    onChange={(e) => setNewStaffPhone(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Role</label>
                  <select
                    value={newStaffRole}
                    onChange={(e) => setNewStaffRole(e.target.value as any)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="Laundry Attendant">Laundry Attendant</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Washer">Washer</option>
                    <option value="Dryer Specialist">Dryer Specialist</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Initial Password</label>
                  <input
                    type="password"
                    placeholder="••••••••"
                    value={newStaffPassword}
                    onChange={(e) => setNewStaffPassword(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-2">Work Schedule (Days on Duty)</label>
                <div className="grid grid-cols-7 gap-1.5">
                  {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => {
                    const selected = newStaffDays.includes(day);
                    return (
                      <button
                        type="button"
                        key={day}
                        onClick={() => handleToggleDay(day)}
                        className={`py-2 text-xs font-bold rounded-lg transition-colors cursor-pointer text-center ${
                          selected
                            ? 'bg-[#52c5be] text-gray-950 ring-2 ring-[#2aa09a]'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }`}
                      >
                        {day}
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="pt-2 flex gap-3">
                <button
                  type="button"
                  onClick={() => setShowAddStaffModal(false)}
                  className="flex-1 py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-xl text-sm transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-3 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-sm transition-colors shadow-sm cursor-pointer"
                >
                  Register Staff
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 4: ADD STOCK ITEM */}
      {showAddInventoryModal && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-xl font-extrabold text-gray-950">New Stock Item</h3>
                <p className="text-xs text-gray-500 mt-0.5">Add a new supply or detergent product to inventory.</p>
              </div>
              <button
                onClick={() => setShowAddInventoryModal(false)}
                className="p-1 rounded-full text-gray-400 hover:text-gray-800 cursor-pointer"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleAddInventory} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-700 mb-1.5">Item Name</label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Fabric Softener Concentrated"
                  value={newInvName}
                  onChange={(e) => setNewInvName(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Category</label>
                  <select
                    value={newInvCategory}
                    onChange={(e) => setNewInvCategory(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="Detergent">Detergent</option>
                    <option value="Softener">Softener</option>
                    <option value="Bleach">Bleach</option>
                    <option value="Packaging">Packaging</option>
                    <option value="Supplies">Supplies</option>
                    <option value="Chemicals">Chemicals</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Unit</label>
                  <select
                    value={newInvUnit}
                    onChange={(e) => setNewInvUnit(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  >
                    <option value="kg">Kilograms (kg)</option>
                    <option value="liters">Liters (L)</option>
                    <option value="pcs">Pieces (pcs)</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Initial Quantity</label>
                  <input
                    type="number"
                    required
                    value={newInvQuantity}
                    onChange={(e) => setNewInvQuantity(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 mb-1.5">Low Stock Alert Level</label>
                  <input
                    type="number"
                    required
                    value={newInvMinStock}
                    onChange={(e) => setNewInvMinStock(e.target.value)}
                    className="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:border-[#52c5be] focus:ring-2 focus:ring-[#52c5be]/20"
                  />
                </div>
              </div>

              <div className="pt-2 flex gap-3">
                <button
                  type="button"
                  onClick={() => setShowAddInventoryModal(false)}
                  className="flex-1 py-3 border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-xl text-sm transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-3 bg-[#52c5be] hover:bg-[#47b5ae] text-gray-950 font-bold rounded-xl text-sm transition-colors shadow-sm cursor-pointer"
                >
                  Save Stock Item
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
