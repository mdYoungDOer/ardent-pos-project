import React, { useEffect, useRef } from 'react';
import { FiBell, FiX, FiCheckCircle, FiAlertCircle, FiAlertTriangle, FiInfo } from 'react-icons/fi';
import useNotificationStore from '../../stores/notificationStore';

const NotificationBell = () => {
  const {
    notifications,
    unreadCount,
    isOpen,
    togglePanel,
    closePanel,
    markAsRead,
    removeNotification,
    markAllAsRead,
    clearAll
  } = useNotificationStore();

  const panelRef = useRef(null);

  // Close panel when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (panelRef.current && !panelRef.current.contains(event.target)) {
        closePanel();
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen, closePanel]);

  const getIcon = (iconName) => {
    switch (iconName) {
      case 'check-circle':
        return <FiCheckCircle className="h-4 w-4" />;
      case 'alert-circle':
        return <FiAlertCircle className="h-4 w-4" />;
      case 'alert-triangle':
        return <FiAlertTriangle className="h-4 w-4" />;
      case 'info':
        return <FiInfo className="h-4 w-4" />;
      default:
        return <FiInfo className="h-4 w-4" />;
    }
  };

  const getTypeStyles = (type) => {
    switch (type) {
      case 'success':
        return 'bg-green-50 border-green-200 text-green-800';
      case 'error':
        return 'bg-red-50 border-red-200 text-red-800';
      case 'warning':
        return 'bg-yellow-50 border-yellow-200 text-yellow-800';
      case 'info':
        return 'bg-blue-50 border-blue-200 text-blue-800';
      default:
        return 'bg-gray-50 border-gray-200 text-gray-800';
    }
  };

  const getIconColor = (type) => {
    switch (type) {
      case 'success':
        return 'text-green-500';
      case 'error':
        return 'text-red-500';
      case 'warning':
        return 'text-yellow-500';
      case 'info':
        return 'text-blue-500';
      default:
        return 'text-gray-500';
    }
  };

  const formatTime = (timestamp) => {
    const now = new Date();
    const notificationTime = new Date(timestamp);
    const diffInMinutes = Math.floor((now - notificationTime) / (1000 * 60));

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return `${Math.floor(diffInMinutes / 1440)}d ago`;
  };

  return (
    <div className="relative" ref={panelRef}>
      {/* Notification Bell Button */}
      <button
        onClick={togglePanel}
        className="relative p-2 text-gray-600 hover:text-[#E72F7C] hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-[#E72F7C] focus:ring-offset-2"
      >
        <FiBell className="h-6 w-6" />
        
        {/* Unread Badge */}
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 h-5 w-5 bg-[#E72F7C] text-white text-xs rounded-full flex items-center justify-center font-medium">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Notification Panel */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Notifications</h3>
            <div className="flex items-center space-x-2">
              {unreadCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  className="text-sm text-[#E72F7C] hover:text-[#9a0864] font-medium"
                >
                  Mark all read
                </button>
              )}
              {notifications.length > 0 && (
                <button
                  onClick={clearAll}
                  className="text-sm text-gray-500 hover:text-red-600 font-medium"
                >
                  Clear all
                </button>
              )}
              <button
                onClick={closePanel}
                className="text-gray-400 hover:text-gray-600"
              >
                <FiX className="h-5 w-5" />
              </button>
            </div>
          </div>

          {/* Notifications List */}
          <div className="max-h-96 overflow-y-auto">
            {notifications.length === 0 ? (
              <div className="p-6 text-center">
                <FiBell className="mx-auto h-12 w-12 text-gray-400" />
                <p className="mt-2 text-sm text-gray-500">No notifications yet</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-200">
                {notifications.map((notification) => (
                  <div
                    key={notification.id}
                    className={`p-4 hover:bg-gray-50 transition-colors ${
                      !notification.read ? 'bg-blue-50' : ''
                    }`}
                  >
                    <div className="flex items-start space-x-3">
                      {/* Icon */}
                      <div className={`flex-shrink-0 mt-1 ${getIconColor(notification.type)}`}>
                        {getIcon(notification.icon)}
                      </div>

                      {/* Content */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <p className="text-sm font-medium text-gray-900">
                              {notification.title}
                            </p>
                            <p className="text-sm text-gray-600 mt-1">
                              {notification.message}
                            </p>
                            <p className="text-xs text-gray-400 mt-1">
                              {formatTime(notification.timestamp)}
                            </p>
                          </div>
                          
                          {/* Action Button */}
                          {notification.action && (
                            <button className="ml-2 text-xs text-[#E72F7C] hover:text-[#9a0864] font-medium">
                              {notification.action}
                            </button>
                          )}
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center space-x-2 mt-2">
                          {!notification.read && (
                            <button
                              onClick={() => markAsRead(notification.id)}
                              className="text-xs text-[#E72F7C] hover:text-[#9a0864] font-medium"
                            >
                              Mark as read
                            </button>
                          )}
                          <button
                            onClick={() => removeNotification(notification.id)}
                            className="text-xs text-gray-500 hover:text-red-600 font-medium"
                          >
                            Dismiss
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="p-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
              <p className="text-xs text-gray-500 text-center">
                {unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default NotificationBell;
