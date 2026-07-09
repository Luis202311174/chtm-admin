-- =============================================
-- Performance Indexes for CHTM-RMS
-- Run this in Supabase Dashboard → SQL Editor
-- =============================================

CREATE INDEX IF NOT EXISTS idx_bookings_status ON public.bookings (status);
CREATE INDEX IF NOT EXISTS idx_bookings_start_at ON public.bookings (start_at);
CREATE INDEX IF NOT EXISTS idx_bookings_end_at ON public.bookings (end_at);
CREATE INDEX IF NOT EXISTS idx_bookings_room_id ON public.bookings (room_id);
CREATE INDEX IF NOT EXISTS idx_bookings_room_status_dates ON public.bookings (room_id, status, start_at, end_at);

CREATE INDEX IF NOT EXISTS idx_rooms_status ON public.rooms (status);

CREATE INDEX IF NOT EXISTS idx_users_role ON public.users (role);

CREATE INDEX IF NOT EXISTS idx_archived_bookings_status ON public.archived_bookings (status);
CREATE INDEX IF NOT EXISTS idx_archived_bookings_user_id ON public.archived_bookings (user_id);