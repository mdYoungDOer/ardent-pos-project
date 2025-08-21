import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { HiCog, HiUser, HiCreditCard, HiBell, HiShieldCheck } from 'react-icons/hi'
import api from '../../services/api'
import { useAuthStore } from '../../stores/authStore'
import LoadingSpinner from '../../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general')
  const { user, tenant, updateProfile } = useAuthStore()
  const queryClient = useQueryClient()

  const { data: settings, isLoading } = useQuery(
    'settings',
    () => api.get('/settings').then(res => res.data)
  )

  const updateSettings = useMutation(
    (data) => api.put('/settings', data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('settings')
        toast.success('Settings updated successfully')
      },
      onError: () => {
        toast.error('Failed to update settings')
      }
    }
  )

  const {
    register: registerGeneral,
    handleSubmit: handleGeneralSubmit,
    formState: { errors: generalErrors }
  } = useForm({
    defaultValues: settings?.general || {}
  })

  const {
    register: registerProfile,
    handleSubmit: handleProfileSubmit,
    formState: { errors: profileErrors }
  } = useForm({
    defaultValues: {
      first_name: user?.first_name || '',
      last_name: user?.last_name || '',
      email: user?.email || ''
    }
  })

  const tabs = [
    { id: 'general', name: 'General', icon: HiCog },
    { id: 'profile', name: 'Profile', icon: HiUser },
    { id: 'billing', name: 'Billing', icon: HiCreditCard },
    { id: 'notifications', name: 'Notifications', icon: HiBell },
    { id: 'security', name: 'Security', icon: HiShieldCheck }
  ]

  const onGeneralSubmit = (data) => {
    updateSettings.mutate({ general: data })
  }

  const onProfileSubmit = async (data) => {
    const result = await updateProfile(data)
    if (result.success) {
      queryClient.invalidateQueries('settings')
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-600">Manage your account and business preferences</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <nav className="space-y-1">
            {tabs.map((tab) => {
              const Icon = tab.icon
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                    activeTab === tab.id
                      ? 'bg-primary-50 text-primary border-r-2 border-primary'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                  }`}
                >
                  <Icon className="mr-3 h-5 w-5" />
                  {tab.name}
                </button>
              )
            })}
          </nav>
        </div>

        {/* Content */}
        <div className="lg:col-span-3">
          <div className="bg-white shadow-sm rounded-lg p-6">
            {activeTab === 'general' && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-6">General Settings</h3>
                <form onSubmit={handleGeneralSubmit(onGeneralSubmit)} className="space-y-6">
                  <div>
                    <label className="form-label">Business Name</label>
                    <input
                      type="text"
                      className="form-input"
                      defaultValue={tenant?.name}
                      {...registerGeneral('business_name', {
                        required: 'Business name is required'
                      })}
                    />
                    {generalErrors.business_name && (
                      <p className="form-error">{generalErrors.business_name.message}</p>
                    )}
                  </div>

                  <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                      <label className="form-label">Currency</label>
                      <select
                        className="form-input"
                        {...registerGeneral('currency')}
                      >
                        <option value="NGN">Nigerian Naira (₦)</option>
                        <option value="USD">US Dollar ($)</option>
                        <option value="EUR">Euro (€)</option>
                        <option value="GBP">British Pound (£)</option>
                      </select>
                    </div>

                    <div>
                      <label className="form-label">Tax Rate (%)</label>
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        className="form-input"
                        {...registerGeneral('tax_rate')}
                      />
                    </div>
                  </div>

                  <div>
                    <label className="form-label">Business Address</label>
                    <textarea
                      rows={3}
                      className="form-input"
                      {...registerGeneral('address')}
                    />
                  </div>

                  <div>
                    <button
                      type="submit"
                      disabled={updateSettings.isLoading}
                      className="btn-primary"
                    >
                      {updateSettings.isLoading ? 'Saving...' : 'Save Changes'}
                    </button>
                  </div>
                </form>
              </div>
            )}

            {activeTab === 'profile' && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-6">Profile Information</h3>
                <form onSubmit={handleProfileSubmit(onProfileSubmit)} className="space-y-6">
                  <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                      <label className="form-label">First Name</label>
                      <input
                        type="text"
                        className="form-input"
                        {...registerProfile('first_name', {
                          required: 'First name is required'
                        })}
                      />
                      {profileErrors.first_name && (
                        <p className="form-error">{profileErrors.first_name.message}</p>
                      )}
                    </div>

                    <div>
                      <label className="form-label">Last Name</label>
                      <input
                        type="text"
                        className="form-input"
                        {...registerProfile('last_name', {
                          required: 'Last name is required'
                        })}
                      />
                      {profileErrors.last_name && (
                        <p className="form-error">{profileErrors.last_name.message}</p>
                      )}
                    </div>
                  </div>

                  <div>
                    <label className="form-label">Email Address</label>
                    <input
                      type="email"
                      className="form-input"
                      {...registerProfile('email', {
                        required: 'Email is required',
                        pattern: {
                          value: /^\S+@\S+$/i,
                          message: 'Invalid email address'
                        }
                      })}
                    />
                    {profileErrors.email && (
                      <p className="form-error">{profileErrors.email.message}</p>
                    )}
                  </div>

                  <div>
                    <label className="form-label">Role</label>
                    <input
                      type="text"
                      className="form-input bg-gray-50"
                      value={user?.role?.replace('_', ' ').toUpperCase()}
                      disabled
                    />
                  </div>

                  <div>
                    <button
                      type="submit"
                      className="btn-primary"
                    >
                      Update Profile
                    </button>
                  </div>
                </form>
              </div>
            )}

            {activeTab === 'billing' && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-6">Billing & Subscription</h3>
                <div className="space-y-6">
                  <div className="bg-gray-50 rounded-lg p-4">
                    <h4 className="text-sm font-medium text-gray-900 mb-2">Current Plan</h4>
                    <p className="text-2xl font-bold text-primary capitalize">{tenant?.plan || 'Free'}</p>
                    <p className="text-sm text-gray-500 mt-1">
                      {tenant?.plan === 'free' ? 'No monthly charge' : 'Billed monthly'}
                    </p>
                  </div>

                  <div>
                    <h4 className="text-sm font-medium text-gray-900 mb-4">Upgrade Plan</h4>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                      <div className="border border-gray-200 rounded-lg p-4">
                        <h5 className="font-medium text-gray-900">Basic</h5>
                        <p className="text-2xl font-bold text-gray-900">₦5,000<span className="text-sm font-normal">/mo</span></p>
                        <button className="btn-outline w-full mt-3">Select Plan</button>
                      </div>
                      <div className="border border-primary rounded-lg p-4">
                        <h5 className="font-medium text-gray-900">Pro</h5>
                        <p className="text-2xl font-bold text-gray-900">₦15,000<span className="text-sm font-normal">/mo</span></p>
                        <button className="btn-primary w-full mt-3">Select Plan</button>
                      </div>
                      <div className="border border-gray-200 rounded-lg p-4">
                        <h5 className="font-medium text-gray-900">Enterprise</h5>
                        <p className="text-sm text-gray-500">Contact us</p>
                        <button className="btn-outline w-full mt-3">Contact Sales</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'notifications' && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-6">Notification Preferences</h3>
                <div className="space-y-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="text-sm font-medium text-gray-900">Low Stock Alerts</h4>
                      <p className="text-sm text-gray-500">Get notified when products are running low</p>
                    </div>
                    <input type="checkbox" className="h-4 w-4 text-primary" defaultChecked />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="text-sm font-medium text-gray-900">Daily Sales Summary</h4>
                      <p className="text-sm text-gray-500">Receive daily sales reports via email</p>
                    </div>
                    <input type="checkbox" className="h-4 w-4 text-primary" defaultChecked />
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="text-sm font-medium text-gray-900">New Customer Notifications</h4>
                      <p className="text-sm text-gray-500">Get notified when new customers register</p>
                    </div>
                    <input type="checkbox" className="h-4 w-4 text-primary" />
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'security' && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-6">Security Settings</h3>
                <div className="space-y-6">
                  <div>
                    <h4 className="text-sm font-medium text-gray-900 mb-4">Change Password</h4>
                    <div className="space-y-4 max-w-md">
                      <input
                        type="password"
                        placeholder="Current password"
                        className="form-input"
                      />
                      <input
                        type="password"
                        placeholder="New password"
                        className="form-input"
                      />
                      <input
                        type="password"
                        placeholder="Confirm new password"
                        className="form-input"
                      />
                      <button className="btn-primary">Update Password</button>
                    </div>
                  </div>

                  <div className="border-t border-gray-200 pt-6">
                    <h4 className="text-sm font-medium text-gray-900 mb-4">Two-Factor Authentication</h4>
                    <p className="text-sm text-gray-500 mb-4">
                      Add an extra layer of security to your account
                    </p>
                    <button className="btn-outline">Enable 2FA</button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default SettingsPage
