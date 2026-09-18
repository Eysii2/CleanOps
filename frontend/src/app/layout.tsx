import type { Metadata } from 'next';
import './globals.css';
import { AuthProvider } from '@/components/AuthProvider';

export const metadata: Metadata = {
  title: 'CleanOps Laundry Management System',
  description: 'Turning daily loads into organized growth. Powered by Next.js & Supabase.',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <head>
        <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        />
      </head>
      <body className="bg-cleanops-dark min-h-screen text-cleanops-light antialiased">
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
