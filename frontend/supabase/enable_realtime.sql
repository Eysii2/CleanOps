-- ==============================================================================
-- ENABLE SUPABASE REALTIME ON ALL TABLES
-- ==============================================================================
-- Run this in Supabase Dashboard -> SQL Editor
-- This enables live/realtime updates so the admin dashboard auto-refreshes
-- ==============================================================================

-- First, make sure the realtime publication exists
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM pg_publication WHERE pubname = 'supabase_realtime'
  ) THEN
    CREATE PUBLICATION supabase_realtime;
  END IF;
END $$;

-- Drop and re-add tables to ensure they're included
-- (Using IF EXISTS to avoid errors if already added)
DO $$
DECLARE
  tbl TEXT;
  tables TEXT[] := ARRAY['orders', 'notifications', 'services', 'inventory', 'machines', 'customers', 'chat_messages'];
BEGIN
  FOREACH tbl IN ARRAY tables
  LOOP
    BEGIN
      EXECUTE format('ALTER PUBLICATION supabase_realtime DROP TABLE IF EXISTS public.%I', tbl);
    EXCEPTION WHEN OTHERS THEN
      -- ignore if table wasn't in publication
    END;
    BEGIN
      EXECUTE format('ALTER PUBLICATION supabase_realtime ADD TABLE public.%I', tbl);
    EXCEPTION WHEN OTHERS THEN
      -- ignore if table is already in publication
    END;
  END LOOP;
END $$;

-- Enable the replica identity to FULL so realtime can track all changes
ALTER TABLE public.orders REPLICA IDENTITY FULL;
ALTER TABLE public.notifications REPLICA IDENTITY FULL;
ALTER TABLE public.services REPLICA IDENTITY FULL;
ALTER TABLE public.inventory REPLICA IDENTITY FULL;
ALTER TABLE public.machines REPLICA IDENTITY FULL;
ALTER TABLE public.customers REPLICA IDENTITY FULL;
ALTER TABLE public.chat_messages REPLICA IDENTITY FULL;

-- Verify: check which tables are in the realtime publication
SELECT schemaname, tablename
FROM pg_publication_tables
WHERE pubname = 'supabase_realtime';
