import React from 'react';

const Logo = ({ size = 'default', className = '' }) => {
  const sizeClasses = {
    small: 'h-8 w-8',
    default: 'h-12 w-12',
    large: 'h-16 w-16',
    xl: 'h-20 w-20'
  };

  const textSizes = {
    small: 'text-lg',
    default: 'text-xl',
    large: 'text-2xl',
    xl: 'text-3xl'
  };

  return (
    <div className={`flex items-center space-x-3 ${className}`}>
      {/* Logo Icon */}
      <div className={`${sizeClasses[size]} bg-[#E72F7C] rounded-xl flex items-center justify-center shadow-lg`}>
        <svg 
          className={`${textSizes[size]} text-black`} 
          viewBox="0 0 24 24" 
          fill="currentColor"
        >
          {/* Bar Chart */}
          <rect x="4" y="14" width="2" height="6" fill="currentColor" />
          <rect x="8" y="10" width="2" height="10" fill="currentColor" />
          <rect x="12" y="6" width="2" height="14" fill="currentColor" />
          
          {/* Upward Arrow */}
          <path 
            d="M6 16 L10 12 L14 16 L18 12" 
            stroke="currentColor" 
            strokeWidth="2" 
            fill="none" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          />
          <path 
            d="M18 12 L18 8" 
            stroke="currentColor" 
            strokeWidth="2" 
            fill="none" 
            strokeLinecap="round" 
            strokeLinejoin="round"
          />
        </svg>
      </div>
      
      {/* Logo Text */}
      <div className="flex flex-col">
        <span className={`font-bold text-[#5D205D] ${textSizes[size]}`}>
          Ardent
        </span>
        <span className={`font-bold text-[#5D205D] ${textSizes[size]}`}>
          POS
        </span>
      </div>
    </div>
  );
};

export default Logo;
