-- ==============================================================================
-- CleanOps - SEED TEST DATA
-- ==============================================================================
-- Run this in Supabase Dashboard -> SQL Editor AFTER running schema.sql
-- This inserts test data to verify the database connection is working.
-- ==============================================================================

-- 1. Insert a test shop (if none exists)
INSERT INTO public.shops (id, shop_name, address, contact_number)
VALUES (1, 'CleanOps Laundry Shop', '123 Main Street, Manila', '09171234567')
ON CONFLICT (id) DO NOTHING;

-- 2. Insert test customers
INSERT INTO public.customers (id, shop_id, fullname, email, phone, address)
VALUES
  (1, 1, 'Maria Santos', 'maria@email.com', '09181111111', '456 Rizal Ave, Manila'),
  (2, 1, 'Juan Dela Cruz', 'juan@email.com', '09182222222', '789 Mabini St, Quezon City'),
  (3, 1, 'Ana Reyes', 'ana@email.com', '09183333333', '321 Bonifacio Blvd, Makati'),
  (4, 1, 'Carlos Garcia', 'carlos@email.com', '09184444444', '654 Luna St, Pasig'),
  (5, 1, 'Sofia Cruz', 'sofia@email.com', '09185555555', '987 Aguinaldo Rd, Taguig')
ON CONFLICT (id) DO NOTHING;

-- 3. Insert test services
INSERT INTO public.services (id, shop_id, service_name, category, price, turnaround_time, is_active, is_promo, promo_description)
VALUES
  (1, 1, 'Regular Wash & Fold', 'Wash & Fold', 65.00, '24 Hours', true, false, NULL),
  (2, 1, 'Express Wash & Fold', 'Wash & Fold', 120.00, '6 Hours', true, true, '50% faster turnaround!'),
  (3, 1, 'Dry Cleaning - Standard', 'Dry Cleaning', 150.00, '48 Hours', true, false, NULL),
  (4, 1, 'Dry Cleaning - Premium', 'Dry Cleaning', 250.00, '24 Hours', true, false, NULL),
  (5, 1, 'Ironing Only', 'Press & Iron', 40.00, '12 Hours', true, false, NULL),
  (6, 1, 'Comforter / Blanket Wash', 'Specialty', 200.00, '48 Hours', true, false, NULL),
  (7, 1, 'Shoe Cleaning', 'Specialty', 180.00, '72 Hours', true, true, 'New service - 10% introductory discount!')
ON CONFLICT (id) DO NOTHING;

-- 4. Insert test orders (various statuses for dashboard testing)
INSERT INTO public.orders (id, order_number, shop_id, customer_id, customer_name, category, total_amount, payment_status, payment_method, status, order_source, notes, created_at)
VALUES
  (1, 'ORD-2025-001', 1, 1, 'Maria Santos', 'Wash & Fold', 195.00, 'Paid', 'GCash', 'Completed', 'walk-in', '3kg regular clothes', NOW() - INTERVAL '5 days'),
  (2, 'ORD-2025-002', 1, 2, 'Juan Dela Cruz', 'Wash & Fold', 130.00, 'Paid', 'Cash', 'Completed', 'walk-in', '2kg mixed load', NOW() - INTERVAL '4 days'),
  (3, 'ORD-2025-003', 1, 3, 'Ana Reyes', 'Dry Cleaning', 750.00, 'Paid', 'Card', 'Ready for Pickup', 'online', '3 blazers for dry cleaning', NOW() - INTERVAL '2 days'),
  (4, 'ORD-2025-004', 1, 4, 'Carlos Garcia', 'Wash & Fold', 260.00, 'Unpaid', 'Cash', 'In Progress', 'walk-in', '4kg whites and colors separated', NOW() - INTERVAL '1 day'),
  (5, 'ORD-2025-005', 1, 5, 'Sofia Cruz', 'Specialty', 400.00, 'Paid', 'GCash', 'Processing', 'online', '2 comforters', NOW() - INTERVAL '12 hours'),
  (6, 'ORD-2025-006', 1, 1, 'Maria Santos', 'Press & Iron', 160.00, 'Unpaid', 'Cash', 'Pending', 'walk-in', '4 formal shirts for ironing', NOW() - INTERVAL '6 hours'),
  (7, 'ORD-2025-007', 1, 2, 'Juan Dela Cruz', 'Wash & Fold', 65.00, 'Paid', 'Cash', 'In Progress', 'walk-in', '1kg quick load', NOW() - INTERVAL '3 hours'),
  (8, 'ORD-2025-008', 1, 3, 'Ana Reyes', 'Specialty', 180.00, 'Unpaid', 'GCash', 'Pending', 'online', 'White sneakers cleaning', NOW() - INTERVAL '1 hour'),
  (9, 'ORD-2025-009', 1, 4, 'Carlos Garcia', 'Dry Cleaning', 500.00, 'Paid', 'Card', 'Processing', 'walk-in', '2 suits dry cleaning', NOW() - INTERVAL '30 minutes'),
  (10, 'ORD-2025-010', 1, 5, 'Sofia Cruz', 'Wash & Fold', 390.00, 'Unpaid', 'Cash', 'Pending', 'walk-in', '6kg mixed laundry', NOW())
ON CONFLICT (id) DO NOTHING;

-- 5. Insert test inventory items
INSERT INTO public.inventory (id, shop_id, item_name, category, quantity, unit, min_stock)
VALUES
  (1, 1, 'Ariel Powder Detergent', 'Detergent', 25.50, 'kg', 10.00),
  (2, 1, 'Downy Fabric Softener', 'Softener', 18.00, 'liters', 8.00),
  (3, 1, 'Clorox Bleach', 'Bleach', 5.20, 'liters', 5.00),
  (4, 1, 'Plastic Packaging Bags (Large)', 'Packaging', 150.00, 'pcs', 50.00),
  (5, 1, 'Plastic Packaging Bags (Small)', 'Packaging', 200.00, 'pcs', 50.00),
  (6, 1, 'Hangers', 'Accessories', 75.00, 'pcs', 30.00),
  (7, 1, 'Stain Remover Spray', 'Specialty', 3.00, 'bottles', 2.00),
  (8, 1, 'Dryer Sheets', 'Accessories', 45.00, 'sheets', 20.00)
ON CONFLICT (id) DO NOTHING;

-- 6. Insert test machines
INSERT INTO public.machines (id, shop_id, machine_name, machine_type, status, current_customer, remaining_time)
VALUES
  (1, 1, 'Washer #1', 'Washer', 'In Operation', 'Carlos Garcia - ORD-2025-004', '25 min'),
  (2, 1, 'Washer #2', 'Washer', 'Available', 'Idle - Ready for load', 'Ready'),
  (3, 1, 'Washer #3', 'Washer', 'In Operation', 'Juan Dela Cruz - ORD-2025-007', '40 min'),
  (4, 1, 'Dryer #1', 'Dryer', 'Available', 'Idle - Ready for load', 'Ready'),
  (5, 1, 'Dryer #2', 'Dryer', 'Under Maintenance', 'Idle - Under repair', 'N/A'),
  (6, 1, 'Dryer #3', 'Dryer', 'In Operation', 'Sofia Cruz - ORD-2025-005', '15 min')
ON CONFLICT (id) DO NOTHING;

-- 7. Insert test notifications
INSERT INTO public.notifications (shop_id, title, description, unread)
VALUES
  (1, 'New Online Order', 'Ana Reyes placed order ORD-2025-008 for Shoe Cleaning', true),
  (1, 'Low Stock Alert', 'Clorox Bleach is running low (5.2L remaining, minimum: 5L)', true),
  (1, 'Order Ready', 'Order ORD-2025-003 for Ana Reyes is ready for pickup', true),
  (1, 'Payment Received', 'GCash payment of P400.00 received for ORD-2025-005', false),
  (1, 'Machine Maintenance', 'Dryer #2 has been taken offline for maintenance', false);

-- ==============================================================================
-- VERIFICATION: Run these queries to verify data was inserted
-- ==============================================================================
-- SELECT COUNT(*) as total_shops FROM public.shops;
-- SELECT COUNT(*) as total_customers FROM public.customers;
-- SELECT COUNT(*) as total_orders FROM public.orders;
-- SELECT COUNT(*) as total_services FROM public.services;
-- SELECT COUNT(*) as total_inventory FROM public.inventory;
-- SELECT COUNT(*) as total_machines FROM public.machines;
-- SELECT COUNT(*) as total_notifications FROM public.notifications;
