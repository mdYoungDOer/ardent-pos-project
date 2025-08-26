import { Outlet } from 'react-router-dom'
import SuperAdminSidebar from '../components/layout/SuperAdminSidebar'
import SuperAdminHeader from '../components/layout/SuperAdminHeader'

const SuperAdminLayout = () => {
  return (
    <div className="min-h-screen bg-gray-50 flex">
      <SuperAdminSidebar />
      <div className="flex-1 flex flex-col">
        <SuperAdminHeader />
        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

export default SuperAdminLayout
