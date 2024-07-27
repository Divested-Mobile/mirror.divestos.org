#!/bin/bash
#This program is free software: you can redistribute it and/or modify
#it under the terms of the GNU Affero General Public License as published by
#the Free Software Foundation, either version 3 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU Affero General Public License for more details.
#
#You should have received a copy of the GNU Affero General Public License
#along with this program.  If not, see <https://www.gnu.org/licenses/>.

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
