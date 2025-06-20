<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamPrep - Home</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800%3B900&family=Noto+Sans%3Awght%40400%3B500%3B600%3B700%3B800%3B900">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        /* --- Base Animations (Largely Unchanged) --- */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        .fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .notification-pulse { animation: pulse 2s infinite; }

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
                linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
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
        header a.group:hover { color: #16a34a !important; }
        header a.group > span {
            background-image: linear-gradient(to right, #22c55e, #16a34a) !important;
        }

        /* --- Cards & Containers --- */
        .modern-card, .holographic-card {
            /* Unified card style: clean, white, with subtle shadows */
            background: #ffffff;
            backdrop-filter: none;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modern-card:hover, .holographic-card:hover {
            transform: translateY(-5px);
            border-color: #22c55e; /* Green accent on hover */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .holographic-card::before {
            /* Disabled the spinning holographic effect */
            content: none;
        }
        .course-card-item > span {
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
            background: #16a34a; /* Darker green on hover */
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.6s;
        }
        .modern-button:hover::before {
            left: 100%;
        }
        .modern-input, .search-focus {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .modern-input:focus, .search-focus:focus {
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
            animation: none; /* Disabled distracting glow on logo */
        }
        .morphing-blob { 
            /* Disabled floating blobs for a cleaner UI */
            display: none; 
        }

    </style>
</head>

<body style='font-family: Inter, "Noto Sans", sans-serif;'>
    <div class="relative flex size-full min-h-screen flex-col hero-gradient group/design-root overflow-x-hidden cyber-grid">
        <div class="layout-container flex h-full grow flex-col relative z-10">
            <header class="flex items-center justify-between whitespace-nowrap px-4 sm:px-10 py-3 glass-effect sticky top-0 z-50 shadow-sm">
                <div class="flex items-center gap-4 sm:gap-8">
                    <a href="index.php" class="flex items-center gap-3 text-slate-900 hover:scale-105 transition-transform duration-300">
                        <div class="size-8 text-white modern-button rounded-lg p-1.5 shadow-md">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <h2 class="text-gray-800 text-xl font-bold tracking-tight">KURU EXAM</h2>
                    </a>
                    <div class="hidden sm:flex items-center gap-8">
                        <a class="text-gray-600 hover:text-green-600 text-sm font-medium transition-all duration-200 relative group" href="index.php">
                            My Exams
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 transition-all duration-300 group-hover:w-full"></span>
                        </a>
                        <a class="text-gray-600 hover:text-green-600 text-sm font-medium transition-all duration-200 relative group" href="index.php?page=study_plans">
                            Study Plans
                            <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-green-500 transition-all duration-300 group-hover:w-full"></span>
                        </a>
                    </div>
                </div>
        
            </header>

            <main id="layout-content-container" class="flex justify-center flex-1 px-4 sm:px-10 py-12">
                <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
                    <div id="main-content-area">
                        <div class="w-full mb-12 fade-in-up text-center">
                            <h1 class="text-gradient text-5xl sm:text-6xl font-extrabold tracking-tight mb-5">Discover</h1>
                            

                            <div class="relative max-w-xl mx-auto">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256">
                                        <path d="M229.66,218.34l-50.07-50.06a88.11,88.11,0,1,0-11.31,11.31l50.06,50.07a8,8,0,0,0,11.32-11.32ZM40,112a72,72,0,1,1,72,72A72.08,72.08,0,0,1,40,112Z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="course-search-input" onkeyup="filterCourses()" placeholder="Search for a course or exam..." class="modern-input search-focus block w-full rounded-xl p-4 pl-12 text-md shadow-sm" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-6 fade-in-up px-2">
                            <h2 class="text-gray-800 text-3xl font-bold tracking-tight">All Courses</h2>
                            <div class="flex items-center gap-3">
                                <div class="bg-white border rounded-lg px-4 py-2 shadow-sm">
                                    <span class="text-sm text-gray-600 font-medium"><?php echo !empty($subjects) ? count($subjects) : 0; ?> courses available</span>
                                </div>
                            </div>
                        </div>

                        <div id="course-grid-container" class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6">
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="course-card-item holographic-card flex flex-col gap-4 rounded-xl p-6 text-left cursor-pointer" onclick="selectCourse('<?php echo htmlspecialchars($subject['course']); ?>')">
                                        <div class="text-white modern-button p-3 rounded-lg w-fit shadow-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="32px" height="32px" fill="currentColor" viewBox="0 0 256 256">
                                                <path d="M224,48H160a40,40,0,0,0-32,16A40,40,0,0,0,96,48H32A16,16,0,0,0,16,64V192a16,16,0,0,0,16,16H96a24,24,0,0,1,24,24,8,8,0,0,0,16,0,24,24,0,0,1,24-24h64a16,16,0,0,0,16-16V64A16,16,0,0,0,224,48ZM96,192H32V64H96a24,24,0,0,1,24,24V200A39.81,39.81,0,0,0,96,192Zm128,0H160a39.81,39.81,0,0,0-24,8V88a24,24,0,0,1,24-24h64Z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-grow">
                                            <h3 class="course-title text-gray-800 text-lg font-bold leading-tight mb-1">
                                                <?php echo htmlspecialchars($subject['course']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 font-medium">Prepare for your certification with advanced learning materials.</p>
                                        </div>
                                        <span class="text-sm font-bold py-1 px-3 rounded-md w-fit"><?php echo $subject['exam_count']; ?> Exams Available</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p id="no-courses-message" class="text-gray-500 col-span-full text-center py-10 text-lg">No courses found.</p>
                            <?php endif; ?>
                            <div id="no-search-results" class="col-span-full hidden">
                                <div class="text-center py-16 bg-white rounded-xl border">
                                    <div class="w-16 h-16 mx-auto mb-6 text-gray-300">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h3 class="text-gray-800 text-xl font-bold mb-2">No courses match your search</h3>
                                    <p class="text-gray-500 text-md mb-6">Try adjusting your search terms or browse all courses.</p>
                                    <button onclick="document.getElementById('course-search-input').value=''; filterCourses();" class="modern-button px-6 py-2 rounded-lg font-bold text-md shadow-lg">
                                        Clear Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="exam-list-container" class="px-4 pt-4"></div>
                </div>
            </main>

            <footer class="flex justify-center border-t border-solid border-gray-200 bg-white mt-auto">
                <div class="flex max-w-[960px] flex-1 flex-col px-8 py-8 text-center">
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <div class="size-8 text-white modern-button rounded-lg p-1.5 shadow-md">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <h3 class="text-gray-800 text-xl font-bold">KURU PLC</h3>
                    </div>
                    <p class="text-gray-600 text-base leading-relaxed mb-4"></p>
                    <p class="text-gray-500 text-sm font-medium">© 2025 . All rights reserved.</p>
                </div>
            </footer>
        </div>
    </div>
    <script src="assets/scripts.js"></script>
</body>

</html>