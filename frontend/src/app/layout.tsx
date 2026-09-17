import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'CleanOps Laundry Management - Login',
  description: 'Turning daily loads into organized growth. Secure login powered by Supabase.',
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
        {children}
      </body>
    </html>
  );
}
