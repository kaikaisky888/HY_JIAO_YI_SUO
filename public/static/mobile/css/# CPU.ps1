# CPU
Write-Host "=== CPU ===" -ForegroundColor Cyan
Get-WmiObject Win32_Processor | Select-Object Name, NumberOfCores, NumberOfLogicalProcessors, MaxClockSpeed | Format-List

# 内存
Write-Host "=== 内存 ===" -ForegroundColor Cyan
Get-WmiObject Win32_PhysicalMemory | Select-Object BankLabel, Capacity, Speed, Manufacturer | Format-Table -AutoSize

# 硬盘
Write-Host "=== 硬盘 ===" -ForegroundColor Cyan
Get-PhysicalDisk | Select-Object FriendlyName, MediaType, @{N="容量GB";E={[math]::Round($_.Size/1GB,1)}} | Format-Table -AutoSize

# 显卡
Write-Host "=== 显卡 ===" -ForegroundColor Cyan
Get-WmiObject Win32_VideoController | Select-Object Name, @{N="显存MB";E={[math]::Round($_.AdapterRAM/1MB,0)}}, DriverVersion | Format-List

# 主板
Write-Host "=== 主板 ===" -ForegroundColor Cyan
Get-WmiObject Win32_BaseBoard | Select-Object Manufacturer, Product, SerialNumber | Format-List

# 系统
Write-Host "=== 系统 ===" -ForegroundColor Cyan
Get-WmiObject Win32_OperatingSystem | Select-Object Caption, Version, OSArchitecture, @{N="已用内存GB";E={[math]::Round(($_.TotalVisibleMemorySize-$_.FreePhysicalMemory)/1MB,1)}},@{N="总内存GB";E={[math]::Round($_.TotalVisibleMemorySize/1MB,1)}} | Format-List