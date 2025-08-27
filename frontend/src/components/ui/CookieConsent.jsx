import React, { useState, useEffect } from 'react';
import { FiX, FiSettings, FiShield, FiCheck, FiInfo } from 'react-icons/fi';

const CookieConsent = () => {
  const [showBanner, setShowBanner] = useState(false);
  const [showSettings, setShowSettings] = useState(false);
  const [preferences, setPreferences] = useState({
    essential: true, // Always true, cannot be disabled
    performance: false,
    functional: false,
    marketing: false
  });

  useEffect(() => {
    // Check if user has already made a choice
    const cookieConsent = localStorage.getItem('cookieConsent');
    if (!cookieConsent) {
      setShowBanner(true);
    }
  }, []);

  const handleAcceptAll = () => {
    const allPreferences = {
      essential: true,
      performance: true,
      functional: true,
      marketing: true
    };
    setPreferences(allPreferences);
    savePreferences(allPreferences);
    setShowBanner(false);
  };

  const handleAcceptEssential = () => {
    const essentialOnly = {
      essential: true,
      performance: false,
      functional: false,
      marketing: false
    };
    setPreferences(essentialOnly);
    savePreferences(essentialOnly);
    setShowBanner(false);
  };

  const handleSavePreferences = () => {
    savePreferences(preferences);
    setShowBanner(false);
    setShowSettings(false);
  };

  const savePreferences = (prefs) => {
    localStorage.setItem('cookieConsent', JSON.stringify({
      preferences: prefs,
      timestamp: new Date().toISOString(),
      version: '1.0'
    }));
    
    // Apply preferences to actual cookie settings
    applyCookiePreferences(prefs);
  };

  const applyCookiePreferences = (prefs) => {
    // Essential cookies are always enabled
    if (prefs.essential) {
      // Enable essential cookies (session management, security, etc.)
      console.log('Essential cookies enabled');
    }

    if (prefs.performance) {
      // Enable analytics and performance cookies
      console.log('Performance cookies enabled');
      // Initialize Google Analytics, etc.
    } else {
      // Disable analytics
      console.log('Performance cookies disabled');
    }

    if (prefs.functional) {
      // Enable functional cookies (preferences, settings)
      console.log('Functional cookies enabled');
    } else {
      // Disable functional cookies
      console.log('Functional cookies disabled');
    }

    if (prefs.marketing) {
      // Enable marketing cookies (ads, tracking)
      console.log('Marketing cookies enabled');
      // Initialize Facebook Pixel, Google Ads, etc.
    } else {
      // Disable marketing cookies
      console.log('Marketing cookies disabled');
    }
  };

  const togglePreference = (type) => {
    if (type === 'essential') return; // Essential cannot be disabled
    setPreferences(prev => ({
      ...prev,
      [type]: !prev[type]
    }));
  };

  if (!showBanner) return null;

  return (
    <>
      {/* Main Cookie Banner */}
      {!showSettings && (
        <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 p-4 md:p-6">
          <div className="max-w-7xl mx-auto">
            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <FiShield className="h-5 w-5 text-white" />
                  </div>
                  <h3 className="text-lg font-semibold text-gray-900">We value your privacy</h3>
                </div>
                <p className="text-gray-600 text-sm md:text-base">
                  We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. 
                  By clicking "Accept All", you consent to our use of cookies. 
                  <button 
                    onClick={() => setShowSettings(true)}
                    className="text-primary hover:text-primary-600 underline ml-1"
                  >
                    Learn more
                  </button>
                </p>
              </div>
              
              <div className="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <button
                  onClick={handleAcceptEssential}
                  className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                >
                  Essential Only
                </button>
                <button
                  onClick={handleAcceptAll}
                  className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors text-sm font-medium"
                >
                  Accept All
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Detailed Settings Modal */}
      {showSettings && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                    <FiSettings className="h-6 w-6 text-white" />
                  </div>
                  <div>
                    <h2 className="text-xl font-bold text-gray-900">Cookie Preferences</h2>
                    <p className="text-gray-600 text-sm">Manage your cookie settings</p>
                  </div>
                </div>
                <button
                  onClick={() => setShowSettings(false)}
                  className="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <FiX className="h-6 w-6" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-6">
              {/* Essential Cookies */}
              <div className="border border-gray-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                      <FiShield className="h-4 w-4 text-blue-600" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">Essential Cookies</h3>
                      <p className="text-sm text-gray-600">Required for basic website functionality</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Always Active</span>
                    <div className="w-12 h-6 bg-blue-500 rounded-full flex items-center justify-end p-1">
                      <FiCheck className="h-4 w-4 text-white" />
                    </div>
                  </div>
                </div>
                <p className="text-sm text-gray-600">
                  These cookies are necessary for the website to function properly. They enable basic functions like page navigation, access to secure areas, and form submissions.
                </p>
              </div>

              {/* Performance Cookies */}
              <div className="border border-gray-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                      <FiInfo className="h-4 w-4 text-green-600" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">Performance Cookies</h3>
                      <p className="text-sm text-gray-600">Help us understand how visitors interact with our website</p>
                    </div>
                  </div>
                  <button
                    onClick={() => togglePreference('performance')}
                    className={`w-12 h-6 rounded-full transition-colors ${
                      preferences.performance ? 'bg-primary' : 'bg-gray-300'
                    }`}
                  >
                    <div className={`w-4 h-4 bg-white rounded-full transition-transform ${
                      preferences.performance ? 'translate-x-6' : 'translate-x-1'
                    }`} />
                  </button>
                </div>
                <p className="text-sm text-gray-600">
                  These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.
                </p>
              </div>

              {/* Functional Cookies */}
              <div className="border border-gray-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                      <FiSettings className="h-4 w-4 text-purple-600" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">Functional Cookies</h3>
                      <p className="text-sm text-gray-600">Remember your preferences and settings</p>
                    </div>
                  </div>
                  <button
                    onClick={() => togglePreference('functional')}
                    className={`w-12 h-6 rounded-full transition-colors ${
                      preferences.functional ? 'bg-primary' : 'bg-gray-300'
                    }`}
                  >
                    <div className={`w-4 h-4 bg-white rounded-full transition-transform ${
                      preferences.functional ? 'translate-x-6' : 'translate-x-1'
                    }`} />
                  </button>
                </div>
                <p className="text-sm text-gray-600">
                  These cookies enable enhanced functionality and personalization, such as remembering your preferences and settings.
                </p>
              </div>

              {/* Marketing Cookies */}
              <div className="border border-gray-200 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                      <FiInfo className="h-4 w-4 text-orange-600" />
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">Marketing Cookies</h3>
                      <p className="text-sm text-gray-600">Used to deliver relevant advertisements</p>
                    </div>
                  </div>
                  <button
                    onClick={() => togglePreference('marketing')}
                    className={`w-12 h-6 rounded-full transition-colors ${
                      preferences.marketing ? 'bg-primary' : 'bg-gray-300'
                    }`}
                  >
                    <div className={`w-4 h-4 bg-white rounded-full transition-transform ${
                      preferences.marketing ? 'translate-x-6' : 'translate-x-1'
                    }`} />
                  </button>
                </div>
                <p className="text-sm text-gray-600">
                  These cookies are used to track visitors across websites to display relevant and engaging advertisements.
                </p>
              </div>
            </div>

            <div className="p-6 border-t border-gray-200 bg-gray-50">
              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  onClick={() => setShowSettings(false)}
                  className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
                >
                  Cancel
                </button>
                <button
                  onClick={handleSavePreferences}
                  className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors text-sm font-medium"
                >
                  Save Preferences
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default CookieConsent;
