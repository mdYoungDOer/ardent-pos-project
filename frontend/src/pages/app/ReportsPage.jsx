import { useState } from 'react'
import { useQuery } from 'react-query'
import { HiDownload, HiCalendar, HiTrendingUp, HiCash, HiShoppingCart } from 'react-icons/hi'
import api from '../../services/api'
import LoadingSpinner from '../../components/ui/LoadingSpinner'

const ReportsPage = () => {
  const [dateRange, setDateRange] = useState('last_30_days')
  const [reportType, setReportType] = useState('sales')

  const { data: reportData, isLoading } = useQuery(
    ['reports', reportType, dateRange],
    () => api.get(`/reports/${reportType}`, {
      params: { period: dateRange }
    }).then(res => res.data)
  )

  const reportTypes = [
    { value: 'sales', label: 'Sales Report', icon: HiCash },
    { value: 'products', label: 'Product Performance', icon: HiShoppingCart },
    { value: 'customers', label: 'Customer Analytics', icon: HiTrendingUp },
    { value: 'inventory', label: 'Inventory Report', icon: HiShoppingCart }
  ]

  const dateRanges = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last_7_days', label: 'Last 7 Days' },
    { value: 'last_30_days', label: 'Last 30 Days' },
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'this_year', label: 'This Year' }
  ]

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
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
          <p className="text-gray-600">Insights into your business performance</p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button className="btn-outline flex items-center">
            <HiDownload className="h-5 w-5 mr-2" />
            Export Report
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white shadow-sm rounded-lg p-6">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <label className="form-label">Report Type</label>
            <select
              className="form-input"
              value={reportType}
              onChange={(e) => setReportType(e.target.value)}
            >
              {reportTypes.map((type) => (
                <option key={type.value} value={type.value}>
                  {type.label}
                </option>
              ))}
            </select>
          </div>
          
          <div>
            <label className="form-label">Date Range</label>
            <select
              className="form-input"
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
            >
              {dateRanges.map((range) => (
                <option key={range.value} value={range.value}>
                  {range.label}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Report Content */}
      <div className="bg-white shadow-sm rounded-lg p-6">
        {reportData ? (
          <div>
            <h3 className="text-lg font-medium text-gray-900 mb-6">
              {reportTypes.find(t => t.value === reportType)?.label} - {dateRanges.find(r => r.value === dateRange)?.label}
            </h3>
            
            {/* Summary Stats */}
            {reportData.summary && (
              <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                {Object.entries(reportData.summary).map(([key, value]) => (
                  <div key={key} className="bg-gray-50 rounded-lg p-4">
                    <dt className="text-sm font-medium text-gray-500 capitalize">
                      {key.replace('_', ' ')}
                    </dt>
                    <dd className="mt-1 text-2xl font-semibold text-gray-900">
                      {typeof value === 'number' && key.includes('amount') 
                        ? `₦${value.toLocaleString()}`
                        : value
                      }
                    </dd>
                  </div>
                ))}
              </div>
            )}

            {/* Detailed Data */}
            {reportData.data && reportData.data.length > 0 && (
              <div className="overflow-x-auto">
                <table className="table">
                  <thead className="table-header">
                    <tr>
                      {Object.keys(reportData.data[0]).map((key) => (
                        <th key={key} className="capitalize">
                          {key.replace('_', ' ')}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="table-body">
                    {reportData.data.map((row, index) => (
                      <tr key={index}>
                        {Object.entries(row).map(([key, value]) => (
                          <td key={key} className="text-sm text-gray-900">
                            {typeof value === 'number' && key.includes('amount')
                              ? `₦${value.toLocaleString()}`
                              : value
                            }
                          </td>
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        ) : (
          <div className="text-center py-12">
            <div className="text-gray-400 text-6xl mb-4">📊</div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No data available</h3>
            <p className="text-gray-500">
              No data found for the selected report type and date range.
            </p>
          </div>
        )}
      </div>
    </div>
  )
}

export default ReportsPage
