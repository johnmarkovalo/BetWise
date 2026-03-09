import React, { useState } from 'react';
import { Calendar, ChevronDown, ChevronRight, Clock, CheckCircle2 } from 'lucide-react';

const KotlinMVPGantt = () => {
  const [expandedStages, setExpandedStages] = useState({});
  const [activeTab, setActiveTab] = useState('gantt');

  const stages = [
    {
      id: 0,
      name: "Prerequisites & Setup",
      duration: "2 hours",
      week: 1,
      day: 1,
      sessions: 1,
      color: "bg-gray-500",
      tasks: [
        { name: "Install Android Studio & tools", hours: 0.5, week: 1, day: "Mon" },
        { name: "Create GitHub repository", hours: 0.5, week: 1, day: "Mon" },
        { name: "Setup development environment", hours: 1, week: 1, day: "Mon" }
      ]
    },
    {
      id: 1,
      name: "Project Setup & Architecture",
      duration: "4 hours",
      week: 1,
      day: 2,
      sessions: 2,
      color: "bg-blue-500",
      tasks: [
        { name: "Create Android project", hours: 1, week: 1, day: "Mon" },
        { name: "Configure build.gradle.kts", hours: 1.5, week: 1, day: "Tue" },
        { name: "Setup package structure", hours: 1, week: 1, day: "Tue" },
        { name: "Configure dependencies & DI", hours: 0.5, week: 1, day: "Tue" }
      ]
    },
    {
      id: 2,
      name: "Basic UI & Service Status",
      duration: "3 hours",
      week: 1,
      day: 3,
      sessions: 1,
      color: "bg-green-500",
      tasks: [
        { name: "Create MainActivity layout", hours: 1, week: 1, day: "Wed" },
        { name: "Implement MainActivity", hours: 1, week: 1, day: "Wed" },
        { name: "Create MainViewModel & StateFlow", hours: 1, week: 1, day: "Wed" }
      ]
    },
    {
      id: 3,
      name: "AccessibilityService Implementation",
      duration: "5 hours",
      week: 1,
      day: 4,
      sessions: 2,
      color: "bg-purple-500",
      tasks: [
        { name: "Create AccessibilityService class", hours: 2, week: 1, day: "Thu" },
        { name: "Configure service XML & manifest", hours: 1, week: 1, day: "Thu" },
        { name: "Create AccessibilityUtils", hours: 1.5, week: 1, day: "Fri" },
        { name: "Integrate with UI", hours: 0.5, week: 1, day: "Fri" }
      ]
    },
    {
      id: 4,
      name: "Random Touch Implementation",
      duration: "4 hours",
      week: 2,
      day: 6,
      sessions: 2,
      color: "bg-orange-500",
      tasks: [
        { name: "Create GestureExecutor class", hours: 1.5, week: 2, day: "Mon" },
        { name: "Implement dispatchGesture", hours: 1.5, week: 2, day: "Mon" },
        { name: "Add random coords & test button", hours: 1, week: 2, day: "Tue" }
      ]
    },
    {
      id: 5,
      name: "UI Node Detection",
      duration: "5 hours",
      week: 2,
      day: 8,
      sessions: 2,
      color: "bg-red-500",
      tasks: [
        { name: "Create UiNodeFinder class", hours: 2, week: 2, day: "Wed" },
        { name: "Create TestTargetActivity", hours: 1, week: 2, day: "Wed" },
        { name: "Implement find methods", hours: 1.5, week: 2, day: "Thu" },
        { name: "Test find and tap", hours: 0.5, week: 2, day: "Thu" }
      ]
    },
    {
      id: 6,
      name: "WebSocket Client (Basic)",
      duration: "6 hours",
      week: 2,
      day: 10,
      sessions: 2,
      color: "bg-indigo-500",
      tasks: [
        { name: "Create WebSocket models", hours: 1.5, week: 2, day: "Fri" },
        { name: "Implement WebSocketClient", hours: 1.5, week: 2, day: "Fri" },
        { name: "Create Repository & DI", hours: 2, week: 3, day: "Mon" },
        { name: "Integrate with UI", hours: 1, week: 3, day: "Mon" }
      ]
    },
    {
      id: 7,
      name: "Time Synchronization",
      duration: "4 hours",
      week: 3,
      day: 12,
      sessions: 2,
      color: "bg-pink-500",
      tasks: [
        { name: "Create TimeSyncManager", hours: 2, week: 3, day: "Tue" },
        { name: "Implement NTP-style sync", hours: 1, week: 3, day: "Tue" },
        { name: "Periodic re-sync & UI display", hours: 1, week: 3, day: "Wed" }
      ]
    },
    {
      id: 8,
      name: "Round Scheduler & Execution",
      duration: "6 hours",
      week: 3,
      day: 14,
      sessions: 2,
      color: "bg-teal-500",
      tasks: [
        { name: "Create RoundScheduler", hours: 2, week: 3, day: "Thu" },
        { name: "AlarmManager scheduling", hours: 1, week: 3, day: "Thu" },
        { name: "Handle RoundPrepared", hours: 2, week: 3, day: "Fri" },
        { name: "Execute at timestamp", hours: 1, week: 3, day: "Fri" }
      ]
    },
    {
      id: 9,
      name: "Provider Adapter Pattern",
      duration: "8 hours",
      week: 4,
      day: 16,
      sessions: 3,
      color: "bg-yellow-600",
      tasks: [
        { name: "Design ProviderAdapter interface", hours: 1, week: 4, day: "Mon" },
        { name: "Create EvolutionAdapter", hours: 2, week: 4, day: "Mon" },
        { name: "Betting window detection", hours: 2.5, week: 4, day: "Tue" },
        { name: "Result detection logic", hours: 2.5, week: 4, day: "Wed" }
      ]
    },
    {
      id: 10,
      name: "Testing & Polish",
      duration: "8 hours",
      week: 4,
      day: 19,
      sessions: 3,
      color: "bg-cyan-600",
      tasks: [
        { name: "Device compatibility testing", hours: 2, week: 4, day: "Thu" },
        { name: "Battery optimization", hours: 1, week: 4, day: "Thu" },
        { name: "Error handling & recovery", hours: 3, week: 4, day: "Fri" },
        { name: "Documentation & README", hours: 2, week: 5, day: "Mon" }
      ]
    }
  ];

  const totalWeeks = 5;
  const workDaysPerWeek = 5;
  const totalHours = stages.reduce((sum, stage) => {
    return sum + parseFloat(stage.duration.split(' ')[0]);
  }, 0);

  const toggleStage = (id) => {
    setExpandedStages(prev => ({
      ...prev,
      [id]: !prev[id]
    }));
  };

  const GanttChart = () => {
    const weeks = Array.from({ length: totalWeeks }, (_, i) => i + 1);
    const daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

    return (
      <div className="overflow-x-auto">
        <div className="min-w-[1200px]">
          {/* Header */}
          <div className="flex border-b border-gray-300 bg-gray-50">
            <div className="w-64 p-3 font-semibold">Stage</div>
            <div className="flex-1">
              <div className="flex">
                {weeks.map(week => (
                  <div key={week} className="flex-1 border-l border-gray-300">
                    <div className="text-center font-semibold text-sm p-2 bg-gray-100">
                      Week {week}
                    </div>
                    <div className="flex">
                      {daysOfWeek.map(day => (
                        <div key={day} className="flex-1 text-center text-xs p-1 border-l border-gray-200">
                          {day}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Timeline Rows */}
          {stages.map(stage => {
            const totalCells = totalWeeks * workDaysPerWeek;
            const startCell = (stage.week - 1) * workDaysPerWeek + (stage.day - 1);
            const startPos = (startCell / totalCells) * 100;
            const width = (stage.sessions / totalCells) * 100;

            return (
              <div key={stage.id} className="flex border-b border-gray-200 hover:bg-gray-50">
                <div className="w-64 p-3">
                  <div className="font-medium text-sm">{stage.name}</div>
                  <div className="text-xs text-gray-500 mt-1">
                    {stage.duration} ({stage.sessions} sessions)
                  </div>
                </div>
                <div className="flex-1 relative h-16">
                  {weeks.map(week => (
                    <React.Fragment key={week}>
                      {daysOfWeek.map((_, dayIdx) => {
                        const cellPos = ((week - 1) * workDaysPerWeek + dayIdx) / totalCells * 100;
                        return (
                          <div 
                            key={`${week}-${dayIdx}`}
                            className="absolute top-0 bottom-0 border-l border-gray-100"
                            style={{ left: `${cellPos}%` }}
                          />
                        );
                      })}
                    </React.Fragment>
                  ))}
                  <div
                    className={`absolute ${stage.color} h-10 rounded mt-3 flex items-center justify-center text-white text-xs font-medium shadow-md`}
                    style={{
                      left: `${startPos}%`,
                      width: `${width}%`
                    }}
                  >
                    W{stage.week}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    );
  };

  const TaskBreakdown = () => {
    return (
      <div className="space-y-3 max-h-[600px] overflow-y-auto pr-4">
        {stages.map(stage => (
          <div key={stage.id} className="border rounded-lg overflow-hidden">
            <button
              onClick={() => toggleStage(stage.id)}
              className="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100"
            >
              <div className="flex items-center gap-3">
                {expandedStages[stage.id] ? <ChevronDown size={20} /> : <ChevronRight size={20} />}
                <div className={`w-3 h-3 rounded-full ${stage.color}`}></div>
                <span className="font-semibold">Stage {stage.id}: {stage.name}</span>
                <span className="text-sm text-gray-500">({stage.duration})</span>
              </div>
              <span className="text-sm text-gray-500">Day {stage.day}</span>
            </button>
            
            {expandedStages[stage.id] && (
              <div className="p-4 bg-white">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b">
                      <th className="text-left py-2">Task</th>
                      <th className="text-center py-2">Hours</th>
                      <th className="text-right py-2">Day</th>
                    </tr>
                  </thead>
                  <tbody>
                    {stage.tasks.map((task, idx) => (
                      <tr key={idx} className="border-b last:border-0">
                        <td className="py-2">{task.name}</td>
                        <td className="text-center">{task.hours}h</td>
                        <td className="text-right">Day {task.day}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        ))}
      </div>
    );
  };

  const Timeline = () => {
    return (
      <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {/* Week 1 */}
          <div className="border rounded-lg p-4">
            <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
              <Calendar size={20} className="text-blue-500" />
              Week 1
            </h3>
            <ul className="space-y-2 text-sm">
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Mon:</strong> Setup & Project Foundation (3h)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Tue:</strong> Complete Architecture Setup (3h)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Wed:</strong> Build UI & Status Display (3h)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Thu-Fri:</strong> AccessibilityService (3h each)</span>
              </li>
            </ul>
          </div>

          {/* Week 2 */}
          <div className="border rounded-lg p-4">
            <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
              <Calendar size={20} className="text-purple-500" />
              Week 2
            </h3>
            <ul className="space-y-2 text-sm">
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Mon-Tue:</strong> Touch Injection (3h each)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Wed-Thu:</strong> UI Node Detection (3h each)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Fri:</strong> WebSocket Client Start (3h)</span>
              </li>
            </ul>
          </div>

          {/* Week 3 */}
          <div className="border rounded-lg p-4">
            <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
              <Calendar size={20} className="text-orange-500" />
              Week 3
            </h3>
            <ul className="space-y-2 text-sm">
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Mon:</strong> Complete WebSocket (3h)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Tue-Wed:</strong> Time Sync (3h each)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Thu-Fri:</strong> Round Scheduler (3h each)</span>
              </li>
            </ul>
          </div>

          {/* Week 4 */}
          <div className="border rounded-lg p-4">
            <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
              <Calendar size={20} className="text-red-500" />
              Week 4
            </h3>
            <ul className="space-y-2 text-sm">
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Mon-Wed:</strong> Provider Adapter (3h each)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Thu-Fri:</strong> Testing & Polish (3h each)</span>
              </li>
            </ul>
          </div>

          {/* Week 5 */}
          <div className="border rounded-lg p-4">
            <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
              <Calendar size={20} className="text-green-600" />
              Week 5
            </h3>
            <ul className="space-y-2 text-sm">
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                <span><strong>Mon:</strong> Final Documentation (2h)</span>
              </li>
              <li className="flex items-start gap-2">
                <CheckCircle2 size={16} className="text-blue-500 mt-0.5 flex-shrink-0" />
                <span><strong>Done:</strong> MVP Complete! 🎉</span>
              </li>
            </ul>
          </div>
        </div>

        {/* Weekly Session Planner */}
        <div className="border rounded-lg p-4">
          <h3 className="font-bold text-lg mb-3">Your Weekly Schedule (3 hrs/day, Mon-Fri)</h3>
          <div className="grid grid-cols-5 gap-2">
            {[1, 2, 3, 4, 5].map(week => (
              <div key={week} className="border rounded p-2">
                <div className="font-bold text-sm mb-2 text-center bg-blue-100 p-1 rounded">
                  Week {week}
                </div>
                {['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].map(day => {
                  const dayStages = stages.filter(s => 
                    s.week === week && s.tasks.some(t => t.week === week && t.day === day)
                  );
                  return (
                    <div key={day} className="text-xs py-1 border-b last:border-0">
                      <div className="font-semibold">{day}</div>
                      {dayStages.length > 0 ? (
                        dayStages.map(stage => (
                          <div key={stage.id} className={`${stage.color} text-white px-1 rounded mt-1 text-[10px]`}>
                            S{stage.id}
                          </div>
                        ))
                      ) : (
                        <div className="text-gray-400 text-[10px]">-</div>
                      )}
                    </div>
                  );
                })}
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="w-full max-w-7xl mx-auto p-6 bg-white">
      <div className="mb-6">
        <h1 className="text-3xl font-bold mb-2">Kotlin Android MVP Development Timeline</h1>
        <p className="text-gray-600 mb-4">Adjusted for 3 hours/day, weekdays only (Mon-Fri)</p>
        <div className="flex flex-wrap gap-4 text-sm">
          <div className="flex items-center gap-2">
            <Clock size={16} className="text-blue-500" />
            <span><strong>Total Duration:</strong> 5 weeks (21 working days)</span>
          </div>
          <div className="flex items-center gap-2">
            <CheckCircle2 size={16} className="text-green-500" />
            <span><strong>Total Hours:</strong> {totalHours} hours</span>
          </div>
          <div className="flex items-center gap-2">
            <Calendar size={16} className="text-purple-500" />
            <span><strong>Daily Time:</strong> 3 hours/day</span>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b mb-6">
        <div className="flex gap-1">
          <button
            onClick={() => setActiveTab('gantt')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'gantt' 
                ? 'border-b-2 border-blue-500 text-blue-600' 
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Gantt Chart
          </button>
          <button
            onClick={() => setActiveTab('tasks')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'tasks' 
                ? 'border-b-2 border-blue-500 text-blue-600' 
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Task Breakdown
          </button>
          <button
            onClick={() => setActiveTab('timeline')}
            className={`px-4 py-2 font-medium ${
              activeTab === 'timeline' 
                ? 'border-b-2 border-blue-500 text-blue-600' 
                : 'text-gray-600 hover:text-gray-800'
            }`}
          >
            Weekly Timeline
          </button>
        </div>
      </div>

      {/* Content */}
      <div>
        {activeTab === 'gantt' && <GanttChart />}
        {activeTab === 'tasks' && <TaskBreakdown />}
        {activeTab === 'timeline' && <Timeline />}
      </div>

      {/* Summary */}
      <div className="mt-8 p-4 bg-blue-50 rounded-lg">
        <h3 className="font-semibold mb-2">Your Development Schedule</h3>
        <ul className="text-sm space-y-1 text-gray-700">
          <li>• <strong>3 hours per day</strong> - Perfect for evening sessions (7pm-10pm) or morning (6am-9am)</li>
          <li>• <strong>Weekdays only (Mon-Fri)</strong> - Weekends free for rest or overflow</li>
          <li>• <strong>5 weeks total</strong> - Realistic pace with no burnout</li>
          <li>• <strong>Each stage is a milestone</strong> - Clear progress every 1-2 days</li>
          <li>• <strong>Build buffer:</strong> If you miss a day, catch up on weekend or extend by 1-2 days</li>
        </ul>
      </div>

      <div className="mt-4 p-4 bg-green-50 rounded-lg">
        <h3 className="font-semibold mb-2">Key Milestones (Your Schedule)</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
          <div>
            <strong className="text-green-700">End of Week 2:</strong> Core functionality working (touch + UI detection + WebSocket)
          </div>
          <div>
            <strong className="text-green-700">End of Week 3:</strong> Time sync + Round scheduler complete
          </div>
          <div>
            <strong className="text-green-700">End of Week 5:</strong> Production-ready MVP with full testing
          </div>
        </div>
      </div>

      <div className="mt-4 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
        <h3 className="font-semibold mb-2">💡 Pro Tips for Success</h3>
        <ul className="text-sm space-y-1 text-gray-700">
          <li>• <strong>Start at the same time daily</strong> - Build a routine (e.g., 7pm-10pm every weekday)</li>
          <li>• <strong>Complete one stage before starting next</strong> - Ensures working code at all times</li>
          <li>• <strong>Test immediately</strong> - Don't accumulate untested code</li>
          <li>• <strong>Commit to GitHub daily</strong> - Never lose progress</li>
          <li>• <strong>Use weekends for overflow</strong> - If you fall behind, catch up Saturday</li>
          <li>• <strong>Friday = testing day</strong> - End each week with working features</li>
        </ul>
      </div>
    </div>
  );
};

export default KotlinMVPGantt;