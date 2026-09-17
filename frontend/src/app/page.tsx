'use client';

import React, { useState, useEffect } from 'react';
import LoginForm from '@/components/LoginForm';

const slides = [
  {
    src: '/1.webp',
    alt: 'Professional Laundry Facility',
    quote: 'Smart wash tracking & automated garment care.',
  },
  {
    src: '/2.jpg',
    alt: 'Fast Turnover & Service',
    quote: 'Empowering laundry owners with real-time insight.',
  },
];

export default function LoginPage() {
  const [currentSlide, setCurrentSlide] = useState(0);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentSlide((prev) => (prev + 1) % slides.length);
    }, 4500);
    return () => clearInterval(timer);
  }, []);

  return (
    <main className="min-h-screen flex flex-col lg:flex-row w-full bg-cleanops-dark overflow-hidden">
      {/* Left Column: Visual Showcase & Slideshow */}
      <div className="relative w-full lg:w-[58%] h-64 sm:h-80 lg:h-auto min-h-[300px] lg:min-h-screen overflow-hidden">
        {slides.map((slide, idx) => (
          <div
            key={slide.src}
            className={`absolute inset-0 transition-opacity duration-1000 ease-in-out ${
              idx === currentSlide ? 'opacity-100 scale-100' : 'opacity-0 scale-105'
            } transition-transform duration-1000`}
          >
            <img
              src={slide.src}
              alt={slide.alt}
              className="w-full h-full object-cover object-center brightness-[0.7] contrast-[1.05]"
            />
            {/* Gradient Overlays */}
            <div className="absolute inset-0 bg-gradient-to-t from-cleanops-dark via-transparent to-cleanops-dark/40 lg:hidden" />
            <div className="absolute inset-0 hidden lg:block bg-gradient-to-r from-transparent via-cleanops-dark/30 to-cleanops-dark" />
          </div>
        ))}

        {/* Brand Caption on Slideshow (Desktop) */}
        <div className="hidden lg:flex absolute bottom-12 left-12 right-12 flex-col gap-3 z-10 p-6 rounded-xl bg-cleanops-dark/60 backdrop-blur-md border border-cleanops-teal/20 max-w-lg">
          <div className="flex items-center gap-2">
            {slides.map((_, idx) => (
              <span
                key={idx}
                className={`h-1.5 rounded-full transition-all duration-300 ${
                  idx === currentSlide ? 'w-8 bg-cleanops-teal' : 'w-2 bg-cleanops-light/30'
                }`}
              />
            ))}
          </div>
          <p className="text-sm md:text-base font-medium text-cleanops-light/90 italic">
            &ldquo;{slides[currentSlide].quote}&rdquo;
          </p>
          <span className="text-xs tracking-wider uppercase text-cleanops-teal font-semibold">
            CleanOps Cloud Platform
          </span>
        </div>
      </div>

      {/* Right Column: CleanOps Login Form */}
      <div className="flex-1 flex items-center justify-center p-6 sm:p-10 lg:p-12 z-10">
        <LoginForm />
      </div>
    </main>
  );
}
