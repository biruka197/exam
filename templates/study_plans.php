<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basic Aviation Familiarization</title>
        <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800%3B900&family=Noto+Sans%3Awght%40400%3B500%3B600%3B700%3B800%3B900">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: #228B22;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
            position: relative;
            overflow: hidden;
        }

        .logo::before {
            content: "🇪🇹";
            font-size: 1.5rem;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #228B22;
        }

        .logo-subtitle {
            font-size: 0.9rem;
            color: #666;
        }

        .course-title {
            text-align: center;
            flex-grow: 1;
        }

        .course-title h1 {
            color: #1e3c72;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .course-code {
            color: #666;
            font-size: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .nav-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: #228B22;
        }

        .nav-card h3 {
            color: #1e3c72;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-card p {
            color: #666;
            font-size: 0.95rem;
        }

        .content-section {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #228B22;
        }

        .section-header h2 {
            color: #1e3c72;
            font-size: 2rem;
        }

        .back-btn {
            background: #228B22;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #1e7b1e;
            transform: translateY(-2px);
        }

        .subsection {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            border-left: 4px solid #228B22;
        }

        .subsection h3 {
            color: #1e3c72;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .subsection h4 {
            color: #2a5298;
            font-size: 1.2rem;
            margin: 1.5rem 0 1rem 0;
        }

        .highlight-box {
            background: linear-gradient(135deg, #228B22, #32CD32);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1rem 0;
            box-shadow: 0 4px 15px rgba(34, 139, 34, 0.3);
        }

        .engine-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .engine-type {
            background: rgba(30, 60, 114, 0.1);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(30, 60, 114, 0.2);
        }

        .engine-type h5 {
            color: #1e3c72;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 1rem 0;
        }

        .component-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-top: 3px solid #228B22;
        }

        .component-card h5 {
            color: #1e3c72;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .spec-list {
            list-style: none;
            padding: 0;
        }

        .spec-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            position: relative;
            padding-left: 1.5rem;
        }

        .spec-list li::before {
            content: "✈️";
            position: absolute;
            left: 0;
            top: 0.5rem;
        }

        .progress-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 1001;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #228B22, #32CD32);
            width: 0;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }

            .course-title h1 {
                font-size: 1.4rem;
            }
        }
   
        /* --- Base Animations (Largely Unchanged) --- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.7s ease-out forwards;
        }

        .notification-pulse {
            animation: pulse 2s infinite;
        }

        /* === NEW MODERN GREEN & WHITE THEME === */

        /* --- Main Layout & Background --- */
        .hero-gradient {
            /* Replaced gradient with a clean, light off-white background */
            background-color: #f8f9fa;
            position: relative;
            overflow: hidden;
        }

        .hero-gradient::before {
            /* Removed the overlay for a cleaner look */
            content: none;
        }

        .cyber-grid {
            /* Made the grid much more subtle */
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .glass-effect {
            /* Modern translucent white header/footer */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* --- Typography --- */
        .text-gradient {
            /* Replaced gradient with a solid, high-contrast dark color for readability */
            background: none;
            -webkit-background-clip: initial;
            -webkit-text-fill-color: initial;
            color: #1a202c;
        }

        /* Specific overrides for header links to match the new theme */
        header a.group:hover {
            color: #16a34a !important;
        }

        header a.group>span {
            background-image: linear-gradient(to right, #22c55e, #16a34a) !important;
        }

        /* --- Cards & Containers --- */
        .modern-card,
        .holographic-card {
            /* Unified card style: clean, white, with subtle shadows */
            background: #ffffff;
            backdrop-filter: none;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-card:hover,
        .holographic-card:hover {
            transform: translateY(-5px);
            border-color: #22c55e;
            /* Green accent on hover */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .holographic-card::before {
            /* Disabled the spinning holographic effect */
            content: none;
        }

        .course-card-item>span {
            /* Themed "Exams Available" tag */
            background: #e6f9f0 !important;
            color: #166534 !important;
            font-weight: 600 !important;
        }

        /* --- Buttons & Interactive Elements --- */
        .modern-button {
            /* Solid green button for clear calls-to-action */
            background: #22c55e;
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .modern-button:hover {
            background: #16a34a;
            /* Darker green on hover */
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(34, 197, 94, 0.2), 0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .modern-button::before {
            /* Subtle shimmer effect on buttons */
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s;
        }

        .modern-button:hover::before {
            left: 100%;
        }

        .modern-input,
        .search-focus {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }

        .modern-input:focus,
        .search-focus:focus {
            background: #ffffff;
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2);
            transform: none;
        }

        .neon-border {
            /* Replaced neon effect with a clean green ring */
            border: 2px solid #22c55e;
            background: linear-gradient(white, white) padding-box;
            border-radius: 9999px;
            padding: 2px;
        }

        /* --- Decorative Elements --- */
        .glow-animation {
            animation: none;
            /* Disabled distracting glow on logo */
        }

        .morphing-blob {
            /* Disabled floating blobs for a cleaner UI */
            display: none;
        }
    </style>
</head>

<body>
    <header
        class="flex items-center justify-between whitespace-nowrap px-4 sm:px-10 py-3 glass-effect sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4 sm:gap-8">
            <a href="index.php"
                class="flex items-center gap-3 text-slate-900 hover:scale-105 transition-transform duration-300">
                <div class="size-8 text-white modern-button rounded-lg p-1.5 shadow-md">
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z"
                            fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-gray-800 text-xl font-bold tracking-tight">KURU EXAM</h2>
            </a>
            <div class="hidden sm:flex items-center gap-8">
                <a class="text-gray-600 hover:text-green-600 text-sm font-medium transition-all duration-200 relative group"
                    href="index.php">
                    My Exams
                    <span
                        class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a class="text-gray-600 hover:text-green-600 text-sm font-medium transition-all duration-200 relative group"
                    href="index.php?page=study_plans">
                    Study Plans
                    <span
                        class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
            </div>
        </div>

    </header>
    <div class="progress-indicator">
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo"></div>
                <div class="logo-text">
                    <div class="logo-title">Kuru Study</div>
                    <div class="logo-subtitle">Kuru Study</div>
                </div>
            </div>
            <div class="course-title">
                <h1>Basic Aviation Familiarization</h1>
                <div class="course-code">/GC561 </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Navigation Grid -->
        <div id="navigationGrid" class="nav-grid">
            <div class="nav-card">
                <h3>📚 Course Overview</h3>
                <p>Introduction to the course objectives, primary references, and learning </p>
            </div>

            <div class="nav-card" onclick="showSection('engine-fundamentals')">
                <h3>⚙️ Engine Fundamentals</h3>
                <p>Learn about engine types, heat engines, and the fundamental principles of internal and external
                    combustion engines.</p>
            </div>

            <div class="nav-card" onclick="showSection('gas-turbine')">
                <h3>🔥 Gas Turbine Engines</h3>
                <p>Comprehensive study of gas turbine engine principles, Brayton cycle, and different types of turbine
                    engines.</p>
            </div>

            <div class="nav-card" onclick="showSection('construction')">
                <h3>🏗️ Construction & Operation</h3>
                <p>Detailed examination of GTE construction, major sections, and operational characteristics of each
                    component.</p>
            </div>

            <div class="nav-card" onclick="showSection('lubrication')">
                <h3>🛢️ Lubrication Systems</h3>
                <p>Understanding engine lubrication systems, oil types, dry-sump systems, and lubrication components.
                </p>
            </div>

            <div class="nav-card" onclick="showSection('fuel-systems')">
                <h3>⛽ Fuel Systems</h3>
                <p>Study of fuel system components, fuel pumps, nozzles, electronic controls, and fuel management
                    systems.</p>
            </div>

            <div class="nav-card" onclick="showSection('starting-ignition')">
                <h3>🔌 Starting & Ignition</h3>
                <p>Examination of starting systems, ignition systems, air turbine starters, and starter-generators.</p>
            </div>

            <div class="nav-card" onclick="showSection('auxiliary-systems')">
                <h3>🔧 Auxiliary Systems</h3>
                <p>Learn about engine air systems, indication systems, APUs, and propeller fundamentals and
                    construction.</p>
            </div>
        </div>

        <!-- Content Sections -->


        <div id="engine-fundamentals" class="content-section">
            <div class="section-header">
                <h2>Engine Types & Fundamentals</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="highlight-box">
                <h3>What is a Powerplant?</h3>
                <p>A powerplant is defined as the complete installation of an aircraft engine, propeller, and all
                    necessary accessories for its proper operation.</p>
            </div>

            <div class="subsection">
                <h3>Heat Engine Principles</h3>
                <p>Engines operate as heat engines - mechanical devices designed to convert chemical energy from fuel
                    into heat energy, and subsequently into mechanical energy.</p>

                <div class="engine-types">
                    <div class="engine-type">
                        <h5>🔥 External Combustion Engines</h5>
                        <p>Burn the fuel and air mixture outside the engine. Classic example: Steam engine.</p>
                    </div>
                    <div class="engine-type">
                        <h5>⚡ Internal Combustion Engines</h5>
                        <p>Burn the fuel and air mixture inside the engine. Examples: Reciprocating engines and Gas
                            turbine engines.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h3>Engine Classification by Oxidizer Source</h3>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🚀 Non-Air-Breathing Engines</h5>
                        <p>Carry both fuel and oxidizer (oxygen) needed for heat release. Example: Rocket engines.</p>
                    </div>
                    <div class="component-card">
                        <h5>🌬️ Air-Breathing Engines</h5>
                        <p>Draw oxygen from surrounding air. Include GTEs, Pulse jets, Ram jets, and Reciprocating
                            Engines.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="gas-turbine" class="content-section">
            <div class="section-header">
                <h2>Gas Turbine Engine Fundamentals</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="highlight-box">
                <h3>Thrust Generation Principle</h3>
                <p>The thrust of a jet engine originates from an imbalance of internal force that causes movement. This
                    is not due to pushing against outside air - it can even occur in a vacuum! A gas turbine engine
                    produces thrust by increasing the momentum of the gas it expels.</p>
            </div>

            <div class="subsection">
                <h3>Brayton Cycle</h3>
                <p>GTEs operate on the Brayton cycle - a constant-pressure thermodynamic cycle that releases energy from
                    fuel. The fundamental events include: intake, compression, expansion, power, and exhaust.</p>
            </div>

            <div class="subsection">
                <h3>Types of Gas Turbine Engines</h3>
                <div class="engine-types">
                    <div class="engine-type">
                        <h5>✈️ Turbojet Engine</h5>
                        <p>Produces thrust primarily from the exhaust gas.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🌪️ Turbofan Engine</h5>
                        <p>Uses a large fan to bypass air around the core engine, producing significant thrust from
                            bypassed air.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🛩️ Turboprop Engine</h5>
                        <p>Uses a turbine to drive a propeller. Free-turbine engines use a separate turbine for
                            propeller reduction gearing.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🚁 Turboshaft Engine</h5>
                        <p>Used to drive helicopter transmissions.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Engine Output Classification</h4>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🚀 Thrust-Producing Engines</h5>
                        <p>Turbojet and Turbofan engines produce thrust for forward propulsion.</p>
                    </div>
                    <div class="component-card">
                        <h5>⚙️ Torque-Producing Engines</h5>
                        <p>Turboprop and Turboshaft engines produce torque for propeller or rotor systems.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="construction" class="content-section">
            <div class="section-header">
                <h2>Construction & Operation of GTE</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="highlight-box">
                <h3>Engine Sections</h3>
                <p>A gas turbine engine is fundamentally divided into two sections: the <strong>hot section</strong> and
                    the <strong>cold section</strong>.</p>
            </div>

            <div class="subsection">
                <h3>Major Engine Components</h3>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🌬️ Engine Inlet Section</h5>
                        <p>Features Air Inlet Duct, typically found in turbofan engines. Classified by aircraft speed:
                            subsonic or supersonic (convergent-divergent type).</p>
                    </div>
                    <div class="component-card">
                        <h5>🔄 Engine Compressor Section</h5>
                        <p>Adds energy to air, increasing pressure. Operates on acceleration followed by diffusion
                            principle.</p>
                    </div>
                    <div class="component-card">
                        <h5>🔥 Engine Combustion Section</h5>
                        <p>Where fuel and air mixture is ignited. 25% primary air for combustion, 75% secondary air for
                            cooling and mixing.</p>
                    </div>
                    <div class="component-card">
                        <h5>⚡ Engine Turbine Section</h5>
                        <p>Converts kinetic energy to mechanical energy. Absorbs 60-80% of total pressure, located
                            between combustion chamber and exhaust.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Compressor Types</h4>
                <div class="engine-types">
                    <div class="engine-type">
                        <h5>📏 Axial Flow Compressors</h5>
                        <p>Air flows axially along engine axis. Consists of rotating rotor blades and stationary stator
                            vanes. One stage = one rotor + stator pair.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🌀 Centrifugal Flow Compressors</h5>
                        <p>Three components: impeller, diffuser, and manifold. Diffuser ducts are divergent.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🔄 Combination (Hybrid) Compressor</h5>
                        <p>Uses both axial and centrifugal compressor stages for optimal performance.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Combustion Chamber Types</h4>
                <div class="engine-types">
                    <div class="engine-type">
                        <h5>🥫 Can Type</h5>
                        <p>Individual combustion chambers arranged around the engine.</p>
                    </div>
                    <div class="engine-type">
                        <h5>⭕ Annular Type</h5>
                        <p>Single ring-shaped combustion chamber. Used when engine length must be minimized.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🔗 Can-Annular Type</h5>
                        <p>Combination of can and annular designs for optimal performance.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="lubrication" class="content-section">
            <div class="section-header">
                <h2>Engine Lubrication Systems</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="highlight-box">
                <h3>Lubrication Fundamentals</h3>
                <p>Lubricant is any natural or artificial substance with greasy or oily properties. Lubrication is
                    imperative to minimize friction and maximize efficiency by creating separation between sliding
                    surfaces.</p>
            </div>

            <div class="subsection">
                <h3>Functions of Engine Lubricants</h3>
                <ul class="spec-list">
                    <li><strong>Primary:</strong> Friction reduction through surface separation</li>
                    <li>Providing cooling to engine components</li>
                    <li>Performing and facilitating cleaning</li>
                    <li>Enabling sealing and cushioning</li>
                    <li>Preventing rust and corrosion</li>
                    <li>Performing hydraulic action</li>
                </ul>
            </div>

            <div class="subsection">
                <h3>Turbine Engine Oil Characteristics</h3>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🧪 Oil Composition</h5>
                        <p>Most turbine engine oils have a synthetic base for superior performance under extreme
                            conditions.</p>
                    </div>
                    <div class="component-card">
                        <h5>📊 Oil Quantity</h5>
                        <p>Turbine engines carry smaller oil quantities compared to reciprocating engines due to
                            efficient design.</p>
                    </div>
                    <div class="component-card">
                        <h5>🌡️ Heat Source</h5>
                        <p>Most heat absorbed by oil comes from the bearings during operation.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Dry-Sump Lubrication System</h4>
                <p>The most popular lubrication system for modern gas turbine engines, consisting of three basic
                    subsystems:</p>
                <div class="engine-types">
                    <div class="engine-type">
                        <h5>💨 Pressure Subsystem</h5>
                        <p>Delivers oil under pressure to lubrication points using spur-gear or gerotor pumps.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🔄 Scavenge Subsystem</h5>
                        <p>Returns used oil to tank. Oil cooler is located in this subsystem in hot-tank systems.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🌬️ Vent Subsystem</h5>
                        <p>Maintains proper pressure balance and prevents oil foaming.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>System Components</h4>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>⚙️ Oil Pumps</h5>
                        <p>Spur-gear and gerotor types are most common. All are positive displacement pumps with
                            pressure relief valves.</p>
                    </div>
                    <div class="component-card">
                        <h5>🔧 Bearings</h5>
                        <p>Ball and roller bearings support turbine/compressor shaft, lubricated by oil jets or nozzles.
                        </p>
                    </div>
                    <div class="component-card">
                        <h5>❄️ Oil Coolers</h5>
                        <p>Two types: air-oil and fuel-oil coolers for temperature management.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="fuel-systems" class="content-section">
            <div class="section-header">
                <h2>Engine Fuel Systems</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="subsection">
                <h3>Key Fuel System Components</h3>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🔧 Main Fuel Pump</h5>
                        <p>Engine-driven pump that maintains fuel pressure, controlled by engine-driven fuel control
                            unit.</p>
                    </div>
                    <div class="component-card">
                        <h5>🔍 Low Pressure Filter</h5>
                        <p>Equipped with bypass warning switches. Includes bypass valve for clogged filter situations.
                        </p>
                    </div>
                    <div class="component-card">
                        <h5>💧 Fuel Nozzles</h5>
                        <p>Two basic types: Simplex and Duplex (Dual-Line) for different flow requirements.</p>
                    </div>
                    <div class="component-card">
                        <h5>🚰 Combustion Drain Valve</h5>
                        <p>Drains fuel from nozzle manifolds when engine with pressurizing and dump valve is shut down.
                        </p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h3>Ice Prevention System</h3>
                <div class="highlight-box">
                    <h4>Anti-Icing Heat Sources</h4>
                    <p>To prevent ice crystal formation in jet fuel, two heat sources are typically used:</p>
                    <ul class="spec-list">
                        <li>Engine bleed air</li>
                        <li>Engine lubricating oil</li>
                    </ul>
                </div>
            </div>

            <div class="subsection">
                <h4>Fuel Control Parameters</h4>
                <p>Four basic parameters sensed by turbine engine fuel control:</p>
                <div class="engine-types">
                    <div class="engine-type">
                        <h5>🔄 Engine Speed (RPM)</h5>
                        <p>Primary control parameter for fuel flow management.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🌡️ Compressor Inlet Temperature</h5>
                        <p>Compensates for ambient temperature effects.</p>
                    </div>
                    <div class="engine-type">
                        <h5>📊 Burner Pressure</h5>
                        <p>Monitors combustion chamber pressure. Rapid increases can decrease mass airflow.</p>
                    </div>
                    <div class="engine-type">
                        <h5>🎛️ Throttle Position</h5>
                        <p>Direct pilot input for power setting.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Electronic Engine Controls</h4>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🖥️ Supervisory EEC</h5>
                        <p>Electronic Engine Control that supervises hydro-mechanical fuel control. Reverts to
                            hydro-mechanical if electronic circuitry fails.</p>
                    </div>
                    <div class="component-card">
                        <h5>💻 Full Authority Digital Engine Control (FADEC)</h5>
                        <p>Complete digital control of engine operations with no hydro-mechanical backup.</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="starting-ignition" class="content-section">
            <div class="section-header">
                <h2>Starting and Ignition Systems</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="subsection">
                <h3>GTE Ignition System</h3>
                <div class="highlight-box">
                    <h4>System Purposes</h4>
                    <ul class="spec-list">
                        <li><strong>Primary:</strong> Start the engine</li>
                        <li><strong>Secondary:</strong> Provide standby protection against in-flight flameout</li>
                    </ul>
                </div>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>⚡ Igniter Plugs</h5>
                        <p>Typical gas turbine engines have two igniter plugs. Systems are rated by spark energy in
                            joules.</p>
                    </div>
                    <div class="component-card">
                        <h5>🔄 Starting Sequence</h5>
                        <p>Ignition is turned on before engine rotation starts, fuel is turned on after ignition
                            activation.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h3>GTE Starting System</h3>
                <p>The starter drives or cranks the High Pressure Rotor until the engine reaches self-accelerating
                    speed, overcoming compressor inertia and friction loads.</p>

                <div class="engine-types">
                    <div class="engine-type">
                        <h5>🌪️ Air Turbine Starter</h5>
                        <p>Requires large volume of low-pressure compressed air. Features self-contained splash
                            lubrication and safety features like drive shaft-shear point.</p>
                    </div>
                    <div class="engine-type">
                        <h5>⚡ Starter-Generators</h5>
                        <p>Combine starter and generator functions. No engage/disengage mechanism needed, reduced size
                            and weight, but relatively heavy for torque produced.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h4>Dual-Spool Engine Starting</h4>
                <div class="highlight-box">
                    <p>In dual-spool turbine engines, the starter typically rotates the high-pressure compressor for
                        optimal starting characteristics.</p>
                </div>
            </div>
        </div>

        <div id="auxiliary-systems" class="content-section">
            <div class="section-header">
                <h2>Auxiliary Systems</h2>
                <button class="back-btn" onclick="showNavigation()">← Back to Menu</button>
            </div>

            <div class="subsection">
                <h3>GTE Air System</h3>
                <p>Manages airflow within the engine for various purposes including turbine cooling and anti-icing.</p>
                <div class="highlight-box">
                    <h4>⚠️ Important Note</h4>
                    <p>If air is tapped from engine compressors for bleed air services, the Exhaust Gas Temperature
                        (EGT) will increase.</p>
                </div>
            </div>

            <div class="subsection">
                <h3>Engine Indication Systems</h3>
                <p>Monitor various engine parameters for safe and efficient operation:</p>
                <div class="components-grid">
                    <div class="component-card">
                        <h5>🌡️ EGT Measurement</h5>
                        <p>Exhaust Gas Temperature measured in degrees Celsius or Fahrenheit.</p>
                    </div>
                    <div class="component-card">
                        <h5>📊 Engine Pressure Ratio</h5>
                        <p>Senses ratio of turbine discharge pressure to compressor inlet pressure.</p>
                    </div>
                    <div class="component-card">
                        <h5>🔄 HP Compressor RPM</h5>
                        <p>Percentage RPM system receives signal from tachometer generator.</p>
                    </div>
                    <div class="component-card">
                        <h5>📳 Engine Vibration</h5>
                        <p>Measured in inches per second (IPS) or g-force units.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h3>Auxiliary Power Units (APU)</h3>
                <div class="highlight-box">
                    <h4>APU Definition</h4>
                    <p>A small turbine powerplant used on turbine-powered aircraft to provide electrical power and bleed
                        air on the ground, and serve as backup generator in flight.</p>
                </div>

                <div class="components-grid">
                    <div class="component-card">
                        <h5>⚡ Electric Generator</h5>
                        <p>APU drives an electric generator identical to those on main engines.</p>
                    </div>
                    <div class="component-card">
                        <h5>🌬️ Bleed Air Compressor</h5>
                        <p>Supplies air for heating, cooling, anti-ice, and engine starting. Bleed air load is greater
                            than any other APU load.</p>
                    </div>
                    <div class="component-card">
                        <h5>⛽ Fuel Supply</h5>
                        <p>APU fuel comes from the aircraft fuel tanks.</p>
                    </div>
                </div>
            </div>

            <div class="subsection">
                <h3>Propeller Fundamentals</h3>
                <div class="highlight-box">
                    <h4>Propeller Definition</h4>
                    <p>A rotating airfoil composed of two or more blades attached to a central hub. Converts engine
                        horsepower into useful thrust following Newton's third law of motion.</p>
                </div>

                <div class="subsection">
                    <h4>Propeller Components</h4>
                    <div class="engine-types">
                        <div class="engine-type">
                            <h5>🪃 Blade</h5>
                            <p>Arm from hub to tip that generates thrust through airfoil action.</p>
                        </div>
                        <div class="engine-type">
                            <h5>⚙️ Hub</h5>
                            <p>Central portion that fits onto engine output shaft and holds the blades.</p>
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <h4>Thrust Factors</h4>
                    <p>Five key factors determine propeller blade thrust:</p>
                    <ul class="spec-list">
                        <li>Shape of the airfoil</li>
                        <li>Area of the airfoil section</li>
                        <li>Angle of attack</li>
                        <li>Density of the air</li>
                        <li>Speed of airfoil movement through air</li>
                    </ul>
                </div>

                <div class="subsection">
                    <h4>Propeller Construction Materials</h4>
                    <div class="components-grid">
                        <div class="component-card">
                            <h5>🪵 Wood</h5>
                            <p><strong>Advantages:</strong> Light weight, rigidity, simplicity, economy, ease of
                                replacement<br>
                                <strong>Disadvantages:</strong> Maintenance difficulty, weather warpage, drag
                            </p>
                        </div>
                        <div class="component-card">
                            <h5>🔩 Metal</h5>
                            <p>Aluminum and steel construction. Advantages: Thinner airfoil, less maintenance, lower
                                operating cost. Widely used on light aircraft.</p>
                        </div>
                        <div class="component-card">
                            <h5>🧪 Composite Materials</h5>
                            <p>Plastic resins with glass, carbon, kevlar, or boron fibers. Epoxy matrix offers light
                                weight and high strength; carbon provides greater durability.</p>
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <h4>Propeller Mounting Types</h4>
                    <div class="engine-types">
                        <div class="engine-type">
                            <h5>➡️ Puller (Tractor) Propeller</h5>
                            <p>Mounted upstream, in front of supporting structure. Lower stresses due to undisturbed air
                                rotation.</p>
                        </div>
                        <div class="engine-type">
                            <h5>⬅️ Pusher Propeller</h5>
                            <p>Mounted downstream, behind supporting structure. More susceptible to damage from rocks
                                and debris.</p>
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <h4>Propeller Types & Ice Control</h4>
                    <div class="components-grid">
                        <div class="component-card">
                            <h5>⚙️ Propeller Types</h5>
                            <p>Two main types: Fixed-pitch and Constant-speed propellers found on modern aircraft.</p>
                        </div>
                        <div class="component-card">
                            <h5>❄️ Ice Control Systems</h5>
                            <p>Anti-icing (fluid-based) and Deicing (electrical heating) systems prevent ice formation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentSection = 'navigation';

        function showSection(sectionId) {
            // Hide navigation
            document.getElementById('navigationGrid').style.display = 'none';

            // Hide all content sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
                currentSection = sectionId;
                updateProgress();
            }

            // Scroll to top
            window.scrollTo(0, 0);
        }

        function showNavigation() {
            // Hide all content sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Show navigation
            document.getElementById('navigationGrid').style.display = 'grid';
            currentSection = 'navigation';
            updateProgress();

            // Scroll to top
            window.scrollTo(0, 0);
        }

        function updateProgress() {
            const sections = ['overview', 'engine-fundamentals', 'gas-turbine', 'construction', 'lubrication', 'fuel-systems', 'starting-ignition', 'auxiliary-systems'];
            const currentIndex = sections.indexOf(currentSection);
            const progressPercentage = currentIndex >= 0 ? ((currentIndex + 1) / sections.length) * 100 : 0;

            document.getElementById('progressBar').style.width = progressPercentage + '%';
        }

        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function () {
            // Add hover effects to navigation cards
            const navCards = document.querySelectorAll('.nav-card');
            navCards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Initialize progress
            updateProgress();
        });

        // Keyboard navigation
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && currentSection !== 'navigation') {
                showNavigation();
            }
        });

        // Add scroll progress indicator for content sections
        window.addEventListener('scroll', function () {
            if (currentSection !== 'navigation') {
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight - windowHeight;
                const scrollTop = window.pageYOffset;
                const scrollPercent = (scrollTop / documentHeight) * 100;

                // Update progress bar with scroll progress for current section
                const baseProgress = currentSection === 'overview' ? 0 :
                    currentSection === 'engine-fundamentals' ? 12.5 :
                        currentSection === 'gas-turbine' ? 25 :
                            currentSection === 'construction' ? 37.5 :
                                currentSection === 'lubrication' ? 50 :
                                    currentSection === 'fuel-systems' ? 62.5 :
                                        currentSection === 'starting-ignition' ? 75 :
                                            currentSection === 'auxiliary-systems' ? 87.5 : 0;

                const sectionProgress = (scrollPercent / 100) * 12.5;
                document.getElementById('progressBar').style.width = (baseProgress + sectionProgress) + '%';
            }
        });
    </script>
</body>

</html>