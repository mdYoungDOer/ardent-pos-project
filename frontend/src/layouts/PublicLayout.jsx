import StickyHeader from '../components/layout/StickyHeader'
import Footer from '../components/layout/Footer'
import ChatWidget from '../components/support/ChatWidget'

const PublicLayout = ({ children }) => {
  return (
    <div className="min-h-screen flex flex-col">
      <StickyHeader />
      <main className="flex-1">
        {children}
      </main>
      <Footer />
      <ChatWidget />
    </div>
  )
}

export default PublicLayout
