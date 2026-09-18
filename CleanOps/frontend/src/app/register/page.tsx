import { Metadata } from 'next';
import RegisterForm from '@/components/RegisterForm';

export const metadata: Metadata = {
  title: 'Register Laundry Shop | CleanOps',
  description: 'Register your laundry business on CleanOps Cloud Platform.',
};

export default function RegisterPage() {
  return (
    <main className="min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-10 bg-cleanops-dark relative overflow-hidden">
      {/* Background Ambience Glow */}
      <div className="absolute -top-40 -left-40 w-96 h-96 bg-cleanops-teal/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -bottom-40 -right-40 w-96 h-96 bg-cleanops-accent/10 rounded-full blur-3xl pointer-events-none" />

      <div className="relative z-10 w-full flex justify-center">
        <RegisterForm />
      </div>
    </main>
  );
}
