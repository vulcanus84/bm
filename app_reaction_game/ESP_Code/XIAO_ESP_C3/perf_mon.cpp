#include "perf_mon.h"
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "esp_heap_caps.h"


void taskPerformanceMonitor(void *pvParameters)
{
  const TickType_t delayTime = pdMS_TO_TICKS(5000); // alle 5 Sekunden

  while (true)
  {
    Serial.println("\n================ TASK PERFORMANCE =================");
    Serial.println("Name            Prio  State  StackFree  CPU%  Warnings");
    Serial.println("--------------------------------------------------");

    UBaseType_t taskCount = uxTaskGetNumberOfTasks();
    TaskStatus_t *taskStatusArray =
        (TaskStatus_t *)malloc(taskCount * sizeof(TaskStatus_t)); 

    if (!taskStatusArray)
    {
      Serial.println("ERROR: malloc failed");
      vTaskDelay(delayTime);
      continue;
    }

    uint32_t totalRunTime = 0;
    taskCount = uxTaskGetSystemState(taskStatusArray, taskCount, &totalRunTime);

    for (int i = 0; i < taskCount; i++)
    {
      TaskStatus_t &t = taskStatusArray[i];

      // Task State
      char stateChar;
      switch (t.eCurrentState)
      {
      case eRunning:   stateChar = 'R'; break;
      case eReady:     stateChar = 'Y'; break;
      case eBlocked:   stateChar = 'B'; break;
      case eSuspended: stateChar = 'S'; break;
      case eDeleted:   stateChar = 'D'; break;
      default:         stateChar = '?';
      }

      // Stack (Words → Bytes)
      uint32_t stackFreeBytes = t.usStackHighWaterMark * sizeof(StackType_t);

      // CPU %
      float cpuPercent = 0.0f;
      if (totalRunTime > 0)
      {
        cpuPercent = (100.0f * t.ulRunTimeCounter) / totalRunTime;
      }

      // Warnings
      String warn = "";

      if (stackFreeBytes < 512)
        warn += "LOW_STACK ";

      if (t.uxCurrentPriority >= 8 && t.eCurrentState == eRunning)
        warn += "HIGH_PRIO ";

      if (cpuPercent < 0.1 && t.eCurrentState == eReady)
        warn += "STARVING ";

      Serial.printf(
          "%-15s %4d   %c     %6lu   %5.1f  %s\n",
          t.pcTaskName,
          t.uxCurrentPriority,
          stateChar,
          stackFreeBytes,
          cpuPercent,
          warn.c_str());
    }

    free(taskStatusArray);

    // ===== HEAP OVERVIEW =====
    uint32_t freeHeap      = ESP.getFreeHeap();
    uint32_t minFreeHeap   = ESP.getMinFreeHeap();
    uint32_t largestBlock  = heap_caps_get_largest_free_block(MALLOC_CAP_8BIT);

    Serial.println("--------------------------------------------------");
    Serial.printf("Heap Free      : %6lu bytes\n", freeHeap);
    Serial.printf("Heap Min Free  : %6lu bytes\n", minFreeHeap);
    Serial.printf("Heap Max Block : %6lu bytes\n", largestBlock);

    // Heuristische Warnungen
    if (minFreeHeap < 10000)
      Serial.println("WARNING: Low MinFreeHeap (WiFi/TLS risk)");

    if (largestBlock < 12000)
      Serial.println("WARNING: Heap fragmentation risk");

    Serial.println("==================================================");

    vTaskDelay(delayTime);

  }
}
