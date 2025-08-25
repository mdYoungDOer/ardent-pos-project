import { create } from 'zustand';

const useNotificationStore = create((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isOpen: false,

  // Add a new notification
  addNotification: (notification) => {
    const newNotification = {
      id: Date.now(),
      timestamp: new Date().toISOString(),
      read: false,
      ...notification
    };

    set((state) => ({
      notifications: [newNotification, ...state.notifications],
      unreadCount: state.unreadCount + 1
    }));
  },

  // Mark notification as read
  markAsRead: (notificationId) => {
    set((state) => ({
      notifications: state.notifications.map(notification =>
        notification.id === notificationId
          ? { ...notification, read: true }
          : notification
      ),
      unreadCount: Math.max(0, state.unreadCount - 1)
    }));
  },

  // Mark all notifications as read
  markAllAsRead: () => {
    set((state) => ({
      notifications: state.notifications.map(notification => ({
        ...notification,
        read: true
      })),
      unreadCount: 0
    }));
  },

  // Remove a notification
  removeNotification: (notificationId) => {
    set((state) => {
      const notification = state.notifications.find(n => n.id === notificationId);
      const wasUnread = notification && !notification.read;
      
      return {
        notifications: state.notifications.filter(n => n.id !== notificationId),
        unreadCount: wasUnread ? Math.max(0, state.unreadCount - 1) : state.unreadCount
      };
    });
  },

  // Clear all notifications
  clearAll: () => {
    set({
      notifications: [],
      unreadCount: 0
    });
  },

  // Toggle notification panel
  togglePanel: () => {
    set((state) => ({
      isOpen: !state.isOpen
    }));
  },

  // Close notification panel
  closePanel: () => {
    set({
      isOpen: false
    });
  },

  // Add system notifications
  addSystemNotification: (message, type = 'info') => {
    get().addNotification({
      type,
      title: 'System Notification',
      message,
      icon: type === 'success' ? 'check-circle' : 
            type === 'error' ? 'alert-circle' : 
            type === 'warning' ? 'alert-triangle' : 'info'
    });
  },

  // Add low stock notification
  addLowStockNotification: (productName, currentStock, threshold) => {
    get().addNotification({
      type: 'warning',
      title: 'Low Stock Alert',
      message: `${productName} is running low on stock (${currentStock} remaining, threshold: ${threshold})`,
      icon: 'alert-triangle',
      action: 'View Inventory'
    });
  },

  // Add sale notification
  addSaleNotification: (amount, items) => {
    get().addNotification({
      type: 'success',
      title: 'Sale Completed',
      message: `Sale of ${items} items for $${amount} completed successfully`,
      icon: 'check-circle',
      action: 'View Sale'
    });
  },

  // Add payment notification
  addPaymentNotification: (amount, status) => {
    get().addNotification({
      type: status === 'success' ? 'success' : 'error',
      title: `Payment ${status === 'success' ? 'Successful' : 'Failed'}`,
      message: `Payment of $${amount} ${status === 'success' ? 'completed' : 'failed'}`,
      icon: status === 'success' ? 'check-circle' : 'alert-circle',
      action: 'View Payment'
    });
  }
}));

export default useNotificationStore;
