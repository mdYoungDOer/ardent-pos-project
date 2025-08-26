import { HiHeart, HiLightBulb, HiShieldCheck } from 'react-icons/hi'
import StickyHeader from '../../components/layout/StickyHeader'

const AboutPage = () => {
  const values = [
    {
      icon: HiHeart,
      title: 'Customer-Centric',
      description: 'We put our customers at the heart of everything we do, building solutions that truly solve their problems.'
    },
    {
      icon: HiLightBulb,
      title: 'Innovation',
      description: 'We continuously innovate to stay ahead of the curve and provide cutting-edge solutions.'
    },
    {
      icon: HiShieldCheck,
      title: 'Trust & Security',
      description: 'We maintain the highest standards of security and reliability to protect your business data.'
    }
  ]

  return (
    <div className="bg-white">
      <StickyHeader />
      
      {/* Hero Section */}
      <div className="relative py-16 bg-white overflow-hidden">
        <div className="relative px-4 sm:px-6 lg:px-8">
          <div className="text-lg max-w-prose mx-auto">
            <h1>
              <span className="block text-base text-center text-primary font-semibold tracking-wide uppercase">
                About Us
              </span>
              <span className="mt-2 block text-3xl text-center leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                Empowering Small Businesses
              </span>
            </h1>
            <p className="mt-8 text-xl text-gray-500 leading-8">
              At Ardent POS, we believe that every small business deserves access to powerful, 
              enterprise-grade point of sale technology. Our mission is to level the playing field 
              by providing affordable, intuitive, and feature-rich POS solutions.
            </p>
          </div>
        </div>
      </div>

      {/* Story Section */}
      <div className="py-16 bg-gray-50 overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 space-y-8 sm:px-6 lg:px-8">
          <div className="text-base max-w-prose mx-auto lg:max-w-none">
            <h2 className="text-base text-primary font-semibold tracking-wide uppercase">Our Story</h2>
            <p className="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              Built by entrepreneurs, for entrepreneurs
            </p>
          </div>
          <div className="relative z-10 text-base max-w-prose mx-auto lg:max-w-5xl lg:mx-0 lg:pr-72">
            <p className="text-lg text-gray-500">
              Founded in 2024, Ardent POS was born from the frustration of dealing with outdated, 
              expensive POS systems that weren't designed for modern businesses. Our founders, 
              having run their own retail and hospitality businesses, understood the pain points 
              that small business owners face every day.
            </p>
          </div>
          <div className="lg:grid lg:grid-cols-2 lg:gap-8 lg:items-start">
            <div className="relative z-10">
              <div className="prose prose-indigo text-gray-500 mx-auto lg:max-w-none">
                <p>
                  We set out to create a solution that would be:
                </p>
                <ul>
                  <li><strong>Mobile-first:</strong> Designed for the way businesses operate today</li>
                  <li><strong>Affordable:</strong> Pricing that makes sense for small businesses</li>
                  <li><strong>Intuitive:</strong> Easy to learn and use, even for non-technical users</li>
                  <li><strong>Comprehensive:</strong> All the features you need in one platform</li>
                </ul>
                <p>
                  Today, Ardent POS serves hundreds of businesses across Nigeria and beyond, 
                  helping them streamline operations, increase efficiency, and grow their revenue.
                </p>
              </div>
            </div>
            <div className="mt-12 relative text-base max-w-prose mx-auto lg:mt-0 lg:max-w-none">
              <div className="relative bg-white rounded-lg shadow-lg p-8">
                <blockquote className="relative">
                  <div className="text-2xl leading-9 font-medium text-gray-900">
                    <p>
                      "We wanted to build something that we would have loved to use in our own businesses. 
                      Something that just works, without the complexity and high costs of traditional POS systems."
                    </p>
                  </div>
                  <footer className="mt-8">
                    <div className="flex">
                      <div className="ml-4">
                        <div className="text-base font-medium text-gray-900">Ardent POS Team</div>
                        <div className="text-base text-gray-500">Founders</div>
                      </div>
                    </div>
                  </footer>
                </blockquote>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Values Section */}
      <div className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="lg:text-center">
            <h2 className="text-base text-primary font-semibold tracking-wide uppercase">Our Values</h2>
            <p className="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              What drives us every day
            </p>
          </div>

          <div className="mt-10">
            <div className="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
              {values.map((value) => (
                <div key={value.title} className="relative text-center">
                  <div className="flex items-center justify-center h-16 w-16 rounded-md bg-primary text-white mx-auto">
                    <value.icon className="h-8 w-8" />
                  </div>
                  <p className="mt-4 text-lg leading-6 font-medium text-gray-900">{value.title}</p>
                  <p className="mt-2 text-base text-gray-500">{value.description}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Team Section */}
      <div className="bg-gray-50 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="lg:text-center">
            <h2 className="text-base text-primary font-semibold tracking-wide uppercase">Our Team</h2>
            <p className="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              Meet the people behind Ardent POS
            </p>
            <p className="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
              We're a diverse team of entrepreneurs, developers, and designers passionate about helping businesses succeed.
            </p>
          </div>

          <div className="mt-12 text-center">
            <p className="text-lg text-gray-600">
              Our team is growing! We're always looking for talented individuals who share our passion 
              for helping small businesses thrive.
            </p>
            <div className="mt-8">
              <a href="mailto:careers@ardentpos.com" className="btn-primary">
                Join Our Team
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default AboutPage
