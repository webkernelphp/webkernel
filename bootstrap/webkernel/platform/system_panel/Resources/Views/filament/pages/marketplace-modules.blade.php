<x-filament-panels::page>
    <div style="max-width: 100%; margin: 0 auto;">
        <!-- Hero Section -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 3rem; margin-bottom: 3rem; color: white;">
            <h1 style="font-size: 2rem; font-weight: bold; margin: 0 0 1rem 0;">Webkernel Marketplace</h1>
            <p style="font-size: 1.125rem; margin: 0 0 2rem 0; opacity: 0.9;">Discover and install powerful modules to extend your application</p>

            <!-- Search Bar -->
            <div style="display: flex; gap: 0.5rem; max-width: 500px;">
                <input
                    type="text"
                    wire:model.live="searchQuery"
                    placeholder="Search modules, features, authors..."
                    style="flex: 1; padding: 0.75rem 1rem; border: none; border-radius: 6px; font-size: 1rem;"
                />
                <button style="padding: 0.75rem 1.5rem; background: white; color: #667eea; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Search
                </button>
            </div>
        </div>

        <!-- Category Tabs -->
        <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <button style="padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 0.875rem;">All Modules</button>
            <button style="padding: 0.5rem 1rem; background: #f0f0f0; color: #333; border: none; border-radius: 20px; cursor: pointer; font-size: 0.875rem;">Authentication</button>
            <button style="padding: 0.5rem 1rem; background: #f0f0f0; color: #333; border: none; border-radius: 20px; cursor: pointer; font-size: 0.875rem;">Payments</button>
            <button style="padding: 0.5rem 1rem; background: #f0f0f0; color: #333; border: none; border-radius: 20px; cursor: pointer; font-size: 0.875rem;">Communication</button>
            <button style="padding: 0.5rem 1rem; background: #f0f0f0; color: #333; border: none; border-radius: 20px; cursor: pointer; font-size: 0.875rem;">Analytics</button>
        </div>

        <!-- Loading State -->
        @if($isLoading)
            <div style="text-align: center; padding: 3rem;">
                <div style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 2.5rem; height: 2.5rem; animation: spin 0.6s linear infinite; margin: 0 auto 1rem;"></div>
                <p style="color: #666;">Loading marketplace...</p>
            </div>
        @elseif(empty($this->filteredModules()))
            <div style="text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                <p style="color: #666; font-size: 1.125rem;">No modules found</p>
            </div>
        @else
            <!-- Modules Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                @foreach($this->filteredModules() as $module)
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; transition: box-shadow 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <!-- Module Header with Icon Background -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; text-align: center; color: white;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">{{ $module['name'] }}</h3>
                        </div>

                        <!-- Module Body -->
                        <div style="padding: 1.5rem;">
                            <!-- Author -->
                            <p style="margin: 0 0 0.5rem 0; font-size: 0.875rem; color: #666;">by {{ $module['author'] ?? 'Unknown' }}</p>

                            <!-- Description -->
                            <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.875rem; line-height: 1.5;">{{ substr($module['description'], 0, 100) }}{{ strlen($module['description']) > 100 ? '...' : '' }}</p>

                            <!-- Rating & Stats -->
                            <div style="display: flex; gap: 1rem; margin: 1rem 0; font-size: 0.875rem; color: #666;">
                                @if(isset($module['rating']))
                                    <div>⭐ {{ number_format($module['rating'], 1) }}</div>
                                @endif
                                @if(isset($module['downloads']))
                                    <div>{{ number_format($module['downloads']) }} installs</div>
                                @endif
                            </div>

                            <!-- Version -->
                            @if(isset($module['version']))
                                <p style="margin: 0.5rem 0; font-size: 0.75rem; color: #999;">v{{ $module['version'] }}</p>
                            @endif

                            <!-- Tags -->
                            @if(isset($module['tags']) && !empty($module['tags']))
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0;">
                                    @foreach(array_slice($module['tags'], 0, 3) as $tag)
                                        <span style="display: inline-block; padding: 0.25rem 0.75rem; background: #f0f0f0; color: #666; border-radius: 12px; font-size: 0.75rem;">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Footer -->
                        <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; gap: 0.5rem;">
                            <button
                                wire:click="installModule('{{ $module['id'] }}')"
                                style="flex: 1; padding: 0.75rem 1rem; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.875rem;"
                            >
                                Install
                            </button>
                            <button style="padding: 0.75rem 1rem; background: #f0f0f0; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer;">
                                ℹ️
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</x-filament-panels::page>
