-- ==============================================================================
-- CleanOps - MINIMAL BOOTSTRAP (Run this FIRST in Supabase SQL Editor)
-- ==============================================================================
-- This creates only the essential shop record needed for the customer website
-- to work. Run schema.sql first, then run this.
-- ==============================================================================

-- Insert the default shop so orders can reference shop_id = 1
INSERT INTO public.shops (id, shop_name, address, contact_number)
VALUES (1, 'CleanOps Laundry Shop', '123 Main Street, Manila', '09171234567')
ON CONFLICT (id) DO NOTHING;
