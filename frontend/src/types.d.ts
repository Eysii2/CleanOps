/* Ambient declarations to satisfy TypeScript before npm install */
declare module '@supabase/supabase-js' {
  export function createClient(supabaseUrl: string, supabaseKey: string, options?: any): any;
}

declare module 'lucide-react' {
  import * as React from 'react';
  export const Mail: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const Lock: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const Eye: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const EyeOff: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const AlertCircle: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const CheckCircle2: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const Loader2: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const ShieldCheck: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
  export const UserCheck: React.FC<React.SVGProps<SVGSVGElement> & { className?: string }>;
}
declare module 'next' {
  export type Metadata = Record<string, any>;
}
