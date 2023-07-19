#!/bin/bash

for device in *.yml
do
	deviceName=$(sed 's/.yml//' <<< "$device");
	if [[ "$deviceName" != *"_variant"* ]] || [[ "$deviceName" == *"_variant1" ]]; then
		deviceName=$(sed 's/_variant1//' <<< "$deviceName");
		deviceName=$(echo "$deviceName" | tr '[:upper:]' '[:lower:]');
		mkdir -p "combos/$deviceName"
		bootloaderCombo=$(grep "download_boot" $device);
		recoveryCombo=$(grep "recovery_boot" $device);
		unlockCommand=$(grep "custom_unlock_cmd" $device);

		if [ -n "$bootloaderCombo" ]; then
			bootloaderCombo=$(sed 's/download_boot: //' <<< $bootloaderCombo);
			bootloaderCombo=$(sed 's/kbd>/code>/g' <<< $bootloaderCombo);
			echo "$bootloaderCombo" > "combos/$deviceName/combo-bootloader";
		fi;
		if [ -n "$recoveryCombo" ]; then
			recoveryCombo=$(sed 's/recovery_boot: //' <<< $recoveryCombo);
			recoveryCombo=$(sed 's/kbd>/code>/g' <<< $recoveryCombo);
			echo "$recoveryCombo" > "combos/$deviceName/combo-recovery";
		fi;
		if [ -n "$unlockCommand" ]; then
			unlockCommand=$(sed 's/custom_unlock_cmd: //' <<< $unlockCommand);
			unlockCommand=$(sed "s/'//g" <<< $unlockCommand);
			echo "$unlockCommand" > "combos/$deviceName/command-unlock";
		fi;
	fi;
done
