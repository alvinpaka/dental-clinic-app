
<template>
  <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8 mb-8">
        <div>
          <Link href="/" class="inline-block">
            <div class="flex items-center space-x-3 mb-4">
              <!-- Logo/Icon -->
              <div class="flex items-center justify-center w-10 h-10 bg-blue-100 dark:bg-white/5 rounded-lg backdrop-blur-sm">
                <i class="fa-solid fa-tooth text-xl text-blue-600 dark:text-slate-200"></i>
              </div>
              <div>
                <span class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">
                  DentalPro
                </span>
                <p class="text-xs text-gray-600 dark:text-slate-300">Professional Dental Care</p>
              </div>
            </div>
          </Link>
          <p class="text-gray-600 dark:text-gray-400">
            Streamlining dental practices with cutting-edge technology.
          </p>
        </div>
        
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Product</h3>
          <ul class="space-y-2">
            <li><Link href="#features" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Features</Link></li>
            <!-- <li><Link href="#pricing" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Pricing</Link></li> -->
            <li><Link href="/demo" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Demo</Link></li>
          </ul>
        </div>
        
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Company</h3>
          <ul class="space-y-2">
            <li><Link href="/about" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">About</Link></li>
            <li><Link href="/contact" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Contact</Link></li>
            <!-- <li><Link href="/careers" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Careers</Link></li> -->
          </ul>
        </div>
        
        <div>
          <h3 class="font-semibold mb-4 text-gray-900 dark:text-white">Legal</h3>
          <ul class="space-y-2">
            <!-- <li><Link href="/privacy" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Privacy</Link></li> -->
            <li><Link href="/terms" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Terms</Link></li>
            <li><Link href="/security" class="text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-cyan-400">Security</Link></li>
          </ul>
        </div>
      </div>
      
      <div class="pt-8 border-t border-gray-200 dark:border-gray-800 text-center text-gray-600 dark:text-gray-400">
        <p>&copy; {{ new Date().getFullYear() }} DentalPro. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>
